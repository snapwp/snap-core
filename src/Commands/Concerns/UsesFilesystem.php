<?php

namespace Snap\Commands\Concerns;

trait UsesFilesystem
{
    /**
     * The wp filesystem class.
     * @var \WP_Filesystem_Direct
     */
    private $file;

    /**
     * Setup the WP_Filesystem_Direct instance.
     */
    private function setupFilesystem()
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        \WP_Filesystem();
        global $wp_filesystem;
        $this->file = $wp_filesystem;
    }
}
