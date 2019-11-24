<?php

namespace Snap\Media;

use Snap\Admin\Pages\DynamicImagesPage;
use Snap\Admin\Tables\DynamicImagesTable;
use Snap\Core\Hookable;
use Snap\Core\Snap;
use Snap\Services\Config;
use Snap\Services\Request;
use Snap\Services\Response;

/**
 * Adds the ability to delete dynamic intermediate image sizes from the media admin screen.
 */
class Admin extends Hookable
{
    /**
     * Only load when is_admin() is true.
     *
     * @var boolean
     */
    protected $public = false;

    /**
     * Only enable dynamic image sizes if sizes have been defined.
     */
    public function boot()
    {
        $this->addFilter('post_mime_types', 'addAdditionalMimeTypeSupport');
        $this->addFilter('media_view_settings', 'alterMediaViewSettings');

        if (Config::get('images.dynamic_image_sizes') !== false) {
            $this->addFilter('media_meta', 'addImageSizesMetaToMedia');
            $this->addFilter('attachment_fields_to_edit', 'addIntermediateMgmtFields');
            $this->addFilter('attachment_fields_to_save', 'handleDeleteIntermediateAjax');

            $this->addAction('admin_enqueue_scripts', 'enqueueImageAdminScripts');
            $this->addAction('admin_menu', 'registerDynamicImagesOptionPage');
            $this->addAction('wp_ajax_delete_dynamic_image_sizes', 'handleBulkDeleteSizesAjax');
        }

        $this->addFilter('admin_post_thumbnail_size', 'featuredImageWidgetSize');
    }

    /**
     * Enqueue images.js on the admin media view.
     */
    public function enqueueImageAdminScripts()
    {
        if ($this->isMediaScreen()) {
            \wp_enqueue_script(
                'snap_images_admin_js',
                \get_theme_file_uri('vendor/snapwp/snap-core/assets/images.min.js'),
                [],
                Snap::VERSION,
                true
            );
        }

        if ($this->isDynamicImagesScreen()) {
            \wp_enqueue_script(
                'snap_images_admin_js',
                \get_theme_file_uri('vendor/snapwp/snap-core/assets/dynamic-images.min.js'),
                [],
                Snap::VERSION,
                true
            );
        }
    }

    /**
     * Set better gallery defaults.
     * 
     * @param array $settings
     * @return mixed
     */
    public function alterMediaViewSettings($settings): array
    {
        $settings['galleryDefaults']['link'] = 'none';
        $settings['galleryDefaults']['size'] = 'medium';
        $settings['galleryDefaults']['columns'] = '2';
        return $settings;
    }

    /**
     * Add some additional mime type filters to media pages.
     *
     * @param array $post_mime_types The current list of mime types.
     * @return array The original list with our additional types.
     */
    public function addAdditionalMimeTypeSupport(array $post_mime_types): array
    {
        $additional_mime_types = [
            'application/msword' => [
                \__('Word Docs', 'snap'),
                \__('Manage Word Docs', 'snap'),
                \_n_noop('Word Doc <span class="count">(%s)</span>', 'Word Docs <span class="count">(%s)</span>'),
            ],
            'application/vnd.ms-excel' => [
                \__('Excel Docs', 'snap'),
                \__('Manage Excel Docs', 'snap'),
                \_n_noop('Excel Doc <span class="count">(%s)</span>', 'Excel Docs <span class="count">(%s)</span>'),
            ],
            'application/pdf' => [
                \__('PDFs', 'snap'),
                \__('Manage PDFs', 'snap'),
                \_n_noop('PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>'),
            ],
            'application/zip' => [
                \__('ZIPs', 'snap'),
                \__('Manage ZIPs', 'snap'),
                \_n_noop('ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>'),
            ],
            'text/csv' => [
                \__('CSVs', 'snap'),
                \__('Manage CSVs', 'snap'),
                \_n_noop('CSV <span class="count">(%s)</span>', 'CSVs <span class="count">(%s)</span>'),
            ],
        ];

        return \array_merge($post_mime_types, $additional_mime_types);
    }

    /**
     * Adds the additional meta to all images within the admin media view.
     *
     * Only visible to roles with 'manage_options' cap.
     *
     * @param string   $form_meta The form meta html.
     * @param \WP_Post $post      The current attachment post object.
     * @return string
     */
    public function addImageSizesMetaToMedia($form_meta, $post = null)
    {
        if (wp_attachment_is_image($post->ID) && current_user_can('manage_options')) {
            $meta = wp_get_attachment_metadata($post->ID);

            if (isset($meta['sizes'])) {
                $form_meta .= '<strong>Available sizes: </strong>' . \count($meta['sizes']);
            }
        }

        return $form_meta;
    }

    /**
     * Output the HTML for the dynamic image management.
     *
     * @param array    $form_fields The current output.
     * @param \WP_Post $post        The current attachment.
     * @return array
     */
    public function addIntermediateMgmtFields($form_fields, $post = null)
    {
        $current_screen = \get_current_screen();

        if (isset($current_screen->base) && $current_screen->base === 'post') {
            return $form_fields;
        }

        // Only display for admin level users, and only if an image.
        if (\strpos(\wp_get_referer(), 'upload.php') !== false
            && \wp_attachment_is_image($post->ID)
            && \current_user_can('manage_options')
        ) {
            $meta = wp_get_attachment_metadata($post->ID);

            $public_sizes = \array_diff(get_intermediate_image_sizes(), SizeManager::getDynamicSizes());

            $output = '<hr><p><strong>Generated sizes:</strong></p>';

            if (!empty($meta['sizes'])) {
                $output .= '<table class="widefat fixed striped media">
                    <thead>
                        <tr style="display:table-row;">
                            <td class="manage-column column-cb check-column">
                                <input id="delete-intermediate-all" type="checkbox">
                            </td>
                            <th>Name</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($meta['sizes'] as $key => $value) {
                    if (\in_array($key, $public_sizes)) {
                        continue;
                    }

                    $output .= '<tr style="display:table-row;"><th class="check-column">';
                    $output .= '<input type="checkbox" name="[delete-intermediate][]" value="' . $key . '">';
                    $output .= '</th><td>' . $key . '</td><td>' . $value['width'] . ' x ' . $value['height'] . '</td></tr>';
                }

                $output .= '</tbody></table>
	                <button type="button" style="margin-top: 5px;" class="button-link delete-intermediate-button">Delete selected sizes</button>';
            }

            $form_fields['generated_sizes'] = [
                'input' => 'html',
                'tr' => $output,
            ];
        }

        return $form_fields;
    }

    /**
     * The AJAX handler for deleting a dynamic image size.
     *
     * @param array $post            The current attachment.
     * @param array $attachment_data The POST data passed from the quest.
     * @return array
     */
    public function handleDeleteIntermediateAjax(array $post, array $attachment_data): array
    {
        if (isset($attachment_data['delete-intermediate']) && !empty($attachment_data['delete-intermediate'])) {
            $sizes = $attachment_data['delete-intermediate'];
            SizeManager::deleteDynamicImagesForAttachment($sizes, $post['ID']);
        }

        return $post;
    }

    /**
     * The ajax handler for deleting images from the dynamic images admin screen.
     */
    public function handleBulkDeleteSizesAjax()
    {
        \set_time_limit(0);

        if (!\wp_verify_nonce(\esc_attr($_REQUEST['_wpnonce']), 'bulk-sizes')) {
            Response::jsonError('You have failed our security checks.', 403);
        }

        if (Request::post('bulk-delete') === null) {
            Response::jsonError('Please choose at least one size to delete.', 400);
        }

        $total = Request::post('total');

        if ($total === null) {
            $total = SizeManager::getCountForSize(Request::post('bulk-delete'));
        }

        $processed = SizeManager::deleteDynamicImageBySizeAjax(Request::post('bulk-delete'));
        $completed = (int)Request::post('completed', 0);
        $totalProcessed = $processed + $completed;

        Response::jsonSuccess(
            [
                'total' => $total,
                'complete' => ($processed === true || $totalProcessed >= $total) ? true : $totalProcessed,
            ]
        );
    }

    /**
     * Registers the dynamic images page under the Media menu.
     */
    public function registerDynamicImagesOptionPage()
    {
        \add_submenu_page(
            'upload.php',
            'Dynamic Images',
            'Dynamic Images',
            'manage_options',
            'dynamic-images',
            function () {
                $page = new DynamicImagesPage(new DynamicImagesTable());
                $page->render();
            }
        );
    }

    /**
     * Set the size of the featured image widget.
     *
     * @param string|array $size Original size.
     * @return string|array
     */
    public function featuredImageWidgetSize($size)
    {
        if (\snap_get_image_size('thumbnail') !== false) {
            return 'thumbnail';
        }

        return $size;
    }

    /**
     * A simple utility for checking whether to render the media JS or not.
     *
     * @return bool
     */
    private function isMediaScreen(): bool
    {
        $current_screen = \get_current_screen();

        if (isset($current_screen->base) && $current_screen->base === 'upload') {
            return true;
        }

        return false;
    }

    /**
     * A simple utility for checking whether to render the media JS or not.
     *
     * @return bool
     */
    private function isDynamicImagesScreen(): bool
    {
        $current_screen = \get_current_screen();

        if (isset($current_screen->base) && $current_screen->base === 'media_page_dynamic-images') {
            return true;
        }

        return false;
    }
}
