<?php

namespace Snap\Admin\Tables;

use Snap\Media\SizeManager;
use Snap\Services\Request;
use Snap\Utils\Image;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class DynamicImagesTable extends \WP_List_Table
{
    /**
     * DynamicImagesTable constructor.
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Size', 'sp'), //singular name of the listed records
            'plural' => __('Sizes', 'sp'), //plural name of the listed records
            'ajax' => false //should this table support ajax?
        ]);
    }

    /**
     * Overrides parent method.
     */
    public function prepare_items()
    {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->processBulkAction();

        $this->set_pagination_args([
            'total_items' => Image::getDynamicImageSizesCount(),
            'per_page' => 99999,
        ]);

        $this->items = $this->populateItems();

        \usort($this->items, [$this, 'sort']);
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', 'snap'),
            'width' => __('Width', 'snap'),
            'height' => __('Height', 'snap'),
            'crop' => __('Crop', 'snap'),
            'count' => __('Count of images generated', 'snap'),
        ];
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns(): array
    {
        return [
            'name' => ['name', true],
            'width' => ['width', true],
            'height' => ['height', true],
            'count' => ['count', true],
        ];
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions(): array
    {
        return [
            'bulk-delete' => 'Delete',
        ];
    }

    /**
     * Gets all of the dynamic image sizes, and their counts.
     * @return array
     */
    private function populateItems()
    {
        global $wpdb;

        $all_sizes = \snap_get_image_sizes();
        $output = [];

        foreach ($all_sizes as $size => $meta) {
            if ($meta['generated_on_upload'] === true) {
                continue;
            }

            $output[] = [
                'name' => $size,
                'width' => $meta['width'],
                'height' => $meta['height'],
                'crop' => $meta['crop'],
                'count' => $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM $wpdb->postmeta
                        WHERE `meta_key` = '_wp_attachment_metadata'
                        AND `meta_value` LIKE %s",
                        "%{$size}%"
                    )
                ),
            ];
        }

        return $output;
    }


    /**
     * Render a column when no column specific method exists.
     *
     * @param array  $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
            case 'width':
            case 'height':
            case 'count':
                return $item[$column_name];
                break;
            case 'crop':
                if ($item['crop'] === false) {
                    return 'Not Cropped';
                }

                if (is_array($item['crop'])) {

                    if ($item['crop'][0] == 'center' && $item['crop'][1] == 'center') {
                        return 'Cropped from center';
                    }

                    return 'Cropped from ' . \implode(' ', \array_reverse($item['crop']));
                }

                return 'Cropped from center';
                break;
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     * @return string
     */
    function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            \esc_attr($item['name'])
        );
    }

    /**
     * Sorting callback.
     *
     * @param array $a First item to compare.
     * @param array $b Second item.
     * @return int
     */
    private function sort(array $a, array $b): int
    {
        $order_by = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'name';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        if ($order === 'asc') {
            return $a[$order_by] <=> $b[$order_by];
        }

        return $b[$order_by] <=> $a[$order_by];
    }

    private function processBulkAction()
    {
        if (Request::post('bulk-delete')) {
            if (!\wp_verify_nonce(\esc_attr($_REQUEST['_wpnonce']), 'bulk-' . $this->_args['plural'])) {
                \ wp_die('Go get a life script kiddies');
            }

            $sizes = Request::post('bulk-delete');

            echo sprintf(
                '<div class="notice notice-success" data-sizes="%s"><p>%s</p></div>',
                \esc_attr(\implode(' ', $sizes)),
                __('Deleting the following sizes:<br><b>' . \implode(', ', $sizes) . '</b>', 'snap')
            );

            foreach ($sizes as $size) {
                SizeManager::deleteDynamicImageBySize($size);
            }
        }
    }
}