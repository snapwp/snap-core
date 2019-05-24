<?php

namespace Snap\Commands\Concerns;

use Snap\Core\Snap;

trait Needs_Wordpress
{
    /**
     * Include and boot up WordPress.
     *
     * @since  1.0.0
     */
    private function init_wordpress()
    {
        global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;

        // Trick WP into thinking this is an AJAX request. Helps quieten certain plugins.
        \define('DOING_AJAX', true);
        \define('BASE_PATH', $this->find_wordpress_base_path());
        \define('WP_USE_THEMES', false);
        require(BASE_PATH . 'wp-load.php');
    }

    /**
     * Traverse up the directory structure looking for the current WP base path.
     *
     * @since  1.0.0
     *
     * @return string The base path.
     */
    private function find_wordpress_base_path()
    {
        $dir = \dirname(__FILE__);

        do {
            if (\file_exists($dir . "/wp-config.php") || \file_exists($dir . "/wp-config-sample.php")) {
                return $dir . '/';
            }
        } while ($dir = \realpath("$dir/.."));

        return null;
    }
}
