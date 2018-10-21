<?php

namespace Snap\Images;

use Snap\Core\Hookable;

/**
 * Add dynamic image compatability fixes for various plugins.
 */
class Compatability extends Hookable
{
    /**
     * The filters to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    public $filters = [
        'snap_dynamic_image_source' => 'wp_offload_media_file_removal_fix',
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
    public function wp_offload_media_file_removal_fix($src, $id)
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
}
