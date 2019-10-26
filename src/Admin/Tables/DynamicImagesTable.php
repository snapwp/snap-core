<?php

namespace Snap\Admin\Tables;

use Snap\Utils\Image;

if (!\class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class DynamicImagesTable extends \WP_List_Table
{
    /**
     * DynamicImagesTable constructor.
     */
    public function __construct()
    {
        parent::__construct(
            [
                'singular' => __('Size', 'sp'), //singular name of the listed records
                'plural' => __('Sizes', 'sp'), //plural name of the listed records
                'ajax' => false, //should this table support ajax?
            ]
        );
    }

    /**
     * Overrides parent method.
     */
    public function prepare_items()
    {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->set_pagination_args(
            [
                'total_items' => Image::getDynamicImageSizesCount(),
                'per_page' => 99999,
            ]
        );

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
            'ratio' => __('Ratio', 'snap'),
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
            'ratio' => ['ratio', true],
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
     *
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
                        "%\"{$size}\"%"
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
            case 'width':
            case 'height':
                if ($item[$column_name] <= 0 || $item[$column_name] >= 9000) {
                    return 'Keep aspect';
                }
                return $item[$column_name];
                break;
            case 'name':
            case 'count':
                return $item[$column_name];
                break;
            case 'ratio':
                if ($item['height'] <= 0 || $item['height'] > 9000) {
                    return 'Scaled to width';
                }

                if ($item['width'] <= 0 || $item['width'] > 9000) {
                    return 'Scaled to height';
                }

                $divisor = \gmp_gcd($item['height'], $item['width']);
                return $item['width'] / $divisor . ':' . $item['height'] / $divisor;
                break;
            case 'crop':
                if ($item['crop'] === false) {
                    return 'Not Cropped';
                }

                if (\is_array($item['crop'])) {
                    if ($item['crop'][0] == 'center' && $item['crop'][1] == 'center') {
                        return 'Cropped from center';
                    }

                    return 'Cropped from ' . \implode(' ', \array_reverse($item['crop']));
                }

                return 'Cropped from center';
                break;
            default:
                return \print_r($item, true); //Show the whole array for troubleshooting purposes
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
        return \sprintf(
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
}
