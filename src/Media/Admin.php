<?php

namespace Snap\Media;

use Snap\Core\Snap;
use Snap\Core\Hookable;

/**
 * Adds the ability to delete dynamic intermediate image sizes from the media admin screen.
 */
class Admin extends Hookable
{
    /**
     * The filters to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    public $filters = [
        'post_mime_types' => 'add_additional_mime_type_support',
        'media_meta' => 'add_image_sizes_meta_to_media',
        'attachment_fields_to_edit' => 'add_intermediate_fields',
        'attachment_fields_to_save' => 'handle_delete_intermediate_ajax',
    ];
    
    /**
     * The actions to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    public $actions = [
        'admin_enqueue_scripts' => 'enqueue_image_admin_scripts',
    ];

    /**
     * Only load when is_admin() is true.
     *
     * @since  1.0.0
     * @var boolean
     */
    protected $public = false;

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
                \get_theme_file_uri('vendor/snapwp/snap-core/assets/images.js'),
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
     * @param string  $form_meta The form meta html.
     * @param WP_Post $post      The current attachment post object.
     */
    public function add_image_sizes_meta_to_media($form_meta, $post = null)
    {
        if (wp_attachment_is_image($post->ID) && current_user_can('manage_options')) {
            $meta = wp_get_attachment_metadata($post->ID);

            $form_meta .= '<strong>Available sizes: </strong>' . \count($meta['sizes']);
        }

        return $form_meta;
    }

    public function add_intermediate_fields($form_fields, $post = null)
    {
        // Only display for admin level users, and only if an image.
        if (wp_attachment_is_image($post->ID) && current_user_can('manage_options')) {
            $meta = wp_get_attachment_metadata($post->ID);

            $public_sizes = \array_diff(get_intermediate_image_sizes(), Size_Manager::get_dynamic_sizes());

            $output = '<hr><p><strong>Generated sizes:</strong></p>';

            if (!empty($meta['sizes'])) {
                $output .= '<table class="wp-list-table widefat fixed striped media">
                <thead>
                    <tr style="display:table-row;">
                        <td class="manage-column column-cb check-column"><input id="delete-intermediate-all" type="checkbox"></td>
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
                    <button type="button" class="button-link delete-intermediate-button">Delete Permanently</button>';
            }


            $form_fields['generated_sizes'] = [
                'input'      => 'html',
                'tr' => $output,
            ];
        }

        return $form_fields;
    }

    public function handle_delete_intermediate_ajax($post, $attachment_data)
    {

        if (isset($attachment_data['delete-intermediate']) && !empty($attachment_data['delete-intermediate'])) {
            $meta = wp_get_attachment_metadata($post['ID']);
            $upload_path = wp_get_upload_dir();
            $dir = \pathinfo(get_attached_file($post['ID']), PATHINFO_DIRNAME);

            foreach ($attachment_data['delete-intermediate'] as $size) {
                if ($meta['sizes'][ $size ]) {
                    $file = $meta['sizes'][ $size ]['file'];

                    // Remove size meta from attachment
                    unset($meta['sizes'][ $size ]);
                    wp_delete_file_from_directory(trailingslashit($dir) . $file, $dir);
                }
            }
            
            do_action('snap_dynamic_image_before_delete', $attachment_data['delete-intermediate'], $post['ID']);
            do_action('delete_attachment', $post['ID']);
            do_action('snap_dynamic_image_after_delete', $attachment_data['delete-intermediate'], $post['ID']);

            wp_update_attachment_metadata($post['ID'], $meta);
        }

        return $post;
    }

    private function is_media_screen()
    {
        $current_screen = get_current_screen();

        if (isset($current_screen->base) && $current_screen->base === 'upload') {
            return true;
        }

        return false;
    }
}
