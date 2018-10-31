<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;
use Snap\Core\Snap;

/**
 * All asset (script and style) related functionality.
 *
 * @since  1.0.0
 */
class Assets extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @since 1.0.0
     * @var array
     */
    protected $actions = [
        'wp_enqueue_scripts' => 'enqueue_scripts',
    ];

    /**
     * Adds optional filters if required.
     *
     * @since 1.0.0
     */
    public function boot()
    {
        // Whether to add 'defer' to enqueued scripts.
        if (Snap::config('theme.defer_scripts') && ! \is_admin()) {
            $this->add_filter('script_loader_tag', 'defer_scripts', 10, 2);
        }

        // Whether to remove asset version strings.
        if (Snap::config('theme.remove_asset_versions')) {
            $this->add_filter([ 'style_loader_src', 'script_loader_src' ], 'remove_versions_from_assets', 15);
        }
    }

    /**
     * Optionally replace the default WordPress jQuery with a Google CDN version
     * and enqueue the child theme assets.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        // Get specified jQuery version.
        $jquery_version = Snap::config('theme.use_jquery_cdn');

        // if a valid jQuery version has been specified.
        if (! \is_admin()
            && $jquery_version !== false
            && \version_compare($jquery_version, '0.0.1', '>=') === true
        ) {
            // get all non-deferred scripts, to check for jQuery.
            $defer_exclude_list = Snap::config('theme.defer_scripts_skip');

            \wp_deregister_script('jquery');

            \wp_register_script(
                'jquery',
                "//ajax.googleapis.com/ajax/libs/jquery/{$jquery_version}/jquery.min.js",
                [],
                null,
                ( \is_array($defer_exclude_list) && \in_array('jquery', $defer_exclude_list) ) ? false : true
            );

            \wp_enqueue_script('jquery');
        }
    }

    /**
     * If enabled, adds defer attribute to js scripts.
     *
     * @since 1.0.0
     *
     * @param string $tag    HTML of current script.
     * @param string $handle Handle of current script.
     * @return string HTML output for this script.
     */
    public function defer_scripts($tag, $handle)
    {
        $excludes = Snap::config('theme.defer_scripts_skip');

        // Get the script handles to exclude.
        if (empty($excludes)) {
            $exclude_list = [];
        } else {
            $exclude_list = $excludes;
        }

        // If the defer_scripts_skip option was not present, or was incompatible.
        if (! \is_array($exclude_list)) {
            $exclude_list = [];
        }

        if (\in_array($handle, $exclude_list)) {
            return $tag;
        }

        return \str_replace(' src', ' defer="defer" src', $tag);
    }

    /**
     * Remove version query string from all styles and scripts.
     *
     * @since 1.0.0
     *
     * @param string $src The src URL for the asset.
     * @return string The URL without an asset string.
     */
    public function remove_versions_from_assets($src)
    {
        return $src ? \esc_url(\remove_query_arg('ver', $src)) : false;
    }
}