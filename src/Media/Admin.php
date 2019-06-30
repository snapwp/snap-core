<?php

namespace Snap\Media;

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Services\Config;

/**
 * Adds the ability to delete dynamic intermediate image sizes from the media admin screen.
 */
class Admin extends Hookable
{
    /**
     * The filters to run when booted.
     *
     * @var array
     */
    public $filters = [
        'post_mime_types' => 'add_additional_mime_type_support',
    ];

    /**
     * Only load when is_admin() is true.
     *
     * @since  1.0.0
     * @var boolean
     */
    protected $public = false;

    /**
     * Only enable dynamic image sizes if sizes have been defined.
     *
     * @since 1.0.0
     */
    public function boot()
    {
        if (Config::get('images.dynamic_image_sizes') !== false) {
            $this->addFilter('media_meta', 'add_image_sizes_meta_to_media');
            $this->addFilter('attachment_fields_to_edit', 'add_intermediate_mgmt_fields');
            $this->addFilter('attachment_fields_to_save', 'handle_delete_intermediate_ajax');

            $this->addAction('admin_enqueue_scripts', 'enqueue_image_admin_scripts');
        }
    }

    /**
     * Enqueue images.js on the admin media view.
     *
     * @since  1.0.0
     */
    public function enqueue_image_admin_scripts()
    {
        if ($this->is_media_screen()) {
            \wp_enqueue_script(
                'snap_images_admin_js',
                \get_theme_file_uri('vendor/snapwp/snap-core/assets/images.min.js'),
                [],
                Snap::VERSION,
                true
            );
        }
    }

    /**
     * Add some additional mime type filters to media pages.
     *
     * @since  1.0.0
     *
     * @param  array $post_mime_types The current list of mime types.
     * @return array The original list with our additional types.
     */
    public function add_additional_mime_type_support($post_mime_types)
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
     * @since  1.0.0
     *
     * @param string   $form_meta The form meta html.
     * @param \WP_Post $post The current attachment post object.
     * @return string
     */
    public function add_image_sizes_meta_to_media($form_meta, $post = null)
    {
        if (wp_attachment_is_image($post->ID) && current_user_can('manage_options')) {
            $meta = wp_get_attachment_metadata($post->ID);

            $form_meta .= '<strong>Available sizes: </strong>' . \count($meta['sizes']);
        }

        return $form_meta;
    }

    /**
     * Output the HTML for the dynamic image management.
     *
     * @since 1.0.0
     *
     * @param  array    $form_fields The current output.
     * @param \WP_Post $post The current attachment.
     * @return mixed
     */
    public function add_intermediate_mgmt_fields($form_fields, $post = null)
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
                            <th>Width</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($meta['sizes'] as $key => $value) {
                    if (\in_array($key, $public_sizes)) {
                        continue;
                    }

                    $output .= '<tr style="display:table-row;"><th class="check-column">';
                    $output .= '<input type="checkbox" name="[delete-intermediate][]" value="'.$key.'">';
                    $output .= '</th><td>'.$key.'</td><td>'. $value['width'] . 'x' . $value['height'].'</td></tr>';
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
     * DThe AJAX handler for deleting a dynamic image size.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post The current attachment.
     * @param array    $attachment_data The POST data passed from the quest.
     * @return \WP_Post
     */
    public function handle_delete_intermediate_ajax($post, $attachment_data)
    {
        if (isset($attachment_data['delete-intermediate']) && !empty($attachment_data['delete-intermediate'])) {
            $sizes = $attachment_data['delete-intermediate'];
            $meta = \wp_get_attachment_metadata($post['ID']);
            $dir = \pathinfo(get_attached_file($post['ID']), PATHINFO_DIRNAME);

            foreach ($sizes as $size) {
                if ($meta['sizes'][ $size ]) {
                    $file = $meta['sizes'][ $size ]['file'];

                    // Remove size meta from attachment
                    unset($meta['sizes'][ $size ]);
                    \wp_delete_file_from_directory(\trailingslashit($dir) . $file, $dir);
                }
            }

            /**
             * Fires just before 'delete_attachment' is fired when an intermediate image size is deleted via the
             * dynamic sizes admin UI.
             *
             * @since 1.0.0
             * @param array $sizes List of sizes to be deleted
             * @param int $id The ID of the current attachment.
             */
            \do_action('snap_dynamic_image_before_delete', $sizes, $post['ID']);

            \do_action('delete_attachment', $post['ID']);

            /**
             * Fires just after 'delete_attachment' is fired when an intermediate image size is deleted via the
             * dynamic sizes admin UI.
             *
             * @since 1.0.0
             * @param array $sizes List of sizes to be deleted
             * @param int $id The ID of the current attachment.
             */
            \do_action('snap_dynamic_image_after_delete', $sizes, $post['ID']);

            \wp_update_attachment_metadata($post['ID'], $meta);
        }

        return $post;
    }

    /**
     * A simple utility for checking whether to render the media JS or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_media_screen()
    {
        $current_screen = \get_current_screen();

        if (isset($current_screen->base) && $current_screen->base === 'upload') {
            return true;
        }

        return false;
    }
}
