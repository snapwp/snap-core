<?php

namespace Snap\Compatibility;

use Snap\Core\Hookable;

/**
 * Add dynamic image compatibility fixes for various plugins.
 */
class Compat_Offload_Media extends Hookable
{
    /**
     * The filters to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    public $filters = [
        'snap_dynamic_image_source' => 'wp_offload_media_creation_fix',
        'as3cf_preserve_file_from_local_removal' => 'preserve_file_from_local_removal',
        'as3cf_remove_attachment_paths' => 'ensure_correct_file_deletion',
    ];

    /**
     * The actions to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    public $actions = [
        'snap_dynamic_image_before_delete' => 'pre_delete',
        'snap_dynamic_image_after_delete' => 'post_delete',
    ];

    /**
     * Ensure the parent file always exists when WP Offload Media is present.
     *
     * @since  1.0.0
     *
     * @param  string $src Expected parent file location on local system.
     * @param  int    $id  The attachment ID.
     * @return string
     */
    public function wp_offload_media_creation_fix($src, $id)
    {
        if (isset($GLOBALS['as3cf'])) {
            global $as3cf;

            if ($as3cf->get_setting('remove-local-file') == true) {
                $provider_object = $as3cf->get_attachment_provider_info($id);
                $file = get_attached_file($id, true);

                // Copy original to server
                return $as3cf->plugin_compat->copy_provider_file_to_server($provider_object, $file);
            }
        }

        return $src;
    }

    /**
     * This filter allows you to stop files from being removed from the local server
     * even when using WP Offload Media's "Remove all files from server" tool.
     *
     * @handles `as3cf_preserve_file_from_local_removal`
     *
     * @param bool   $preserve
     * @param string $file_path
     *
     * @return bool
     */
    public function preserve_file_from_local_removal($preserve, $file_path)
    {
        \gc_collect_cycles();

        return $preserve;
    }

    public function pre_delete($sizes, $attachment_id)
    {
        $this->sizes = $sizes;
        $this->meta = get_post_meta($attachment_id, 'amazonS3_info', true);
    }

    public function post_delete($sizes, $attachment_id)
    {
        $this->sizes = [];
        update_post_meta($attachment_id, 'amazonS3_info', $this->meta);
    }

    public function ensure_correct_file_deletion($sizes)
    {
        if (isset($GLOBALS['as3cf'])) {
            global $as3cf;

            if ($as3cf->get_setting('remove-local-file') == true && !empty($this->sizes)) {
                return \array_intersect_key($sizes, \array_flip($this->sizes));
            }
        }

        return $sizes;
    }
}
