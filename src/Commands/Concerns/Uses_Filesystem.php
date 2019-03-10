<?php

namespace Snap\Commands\Concerns;

trait Uses_Filesystem
{
    /**
     * The wp filesystem class.
     * @var \WP_Filesystem_Direct
     */
    private $file;

    /**
     * Setup the WP_Filesystem_Direct instance.
     */
    private function setup_filesystem()
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        \WP_Filesystem();
        global $wp_filesystem;
        $this->file = $wp_filesystem;
    }
}
