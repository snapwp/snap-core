<?php

namespace Snap\Utils;

use Snap\Services\Config;

/**
 * Sidebar and widget utilities.
 *
 * @since 1.0.0
 */
class Theme_Utils
{
    /**
     * Mix-manifest.json contents stored as array.
     *
     * @since 1.0.0
     * @var array|null
     */
    private static $manifest = null;

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @since  1.0.0
     *
     * @param  boolean $remove_query If true, the URL is returned without any query params.
     * @return string The current URL.
     */
    public static function get_current_url($remove_query = false)
    {
        global $wp;

        if ($remove_query === true) {
            return \trailingslashit(\home_url($wp->request));
        }

        return \home_url(\add_query_arg(null, null));
    }

    /**
     * Shortcut to get the include path relative to the active theme directory.
     *
     * @since 1.0.0
     *
     * @param string $path Path to append relative to active theme.
     * @return string
     */
    public static function get_active_theme_path($path)
    {
        return \trailingslashit(\get_stylesheet_directory()) . $path;
    }

    /**
     * Shortcut to get the file URL relative to the active theme directory.
     *
     * @since 1.0.0
     *
     * @param string $path Path to append relative to active theme directory URL.
     * @return string
     */
    public static function get_active_theme_uri($path)
    {
        return \trailingslashit(\get_stylesheet_directory_uri()) . $path;
    }

    /**
     * Retrieves a filename public URL with Webpack version ID if present.
     *
     * @since  1.0.0
     *
     * @param  string $file The asset file to look for.
     * @return string The (possibly versioned) asset URL.
     */
    public static function get_asset_url($file)
    {
        if (static::$manifest === null) {
            static::parse_manifest();
        }

        // There was no manifest or no file present.
        if (static::$manifest === null || ! isset(static::$manifest[ $file ])) {
            return get_stylesheet_directory_uri() . '/dist' . $file;
        }

        return get_stylesheet_directory_uri() . '/dist' . static::$manifest[ $file ];
    }

    /**
     * Transforms a partial name and returns the path to the partial relative to theme root.
     *
     * @since  1.0.0
     * 
     * @param  string $partial The partial name.
     * @return string
     */
    public static function get_path_to_partial($partial)
    {
        $partial = \str_replace(
            ['.php', '.'],
            ['', '/'],
            $partial
        );

        $path = \trailingslashit(Config::get('theme.templates_directory')) . 'partials/' . $partial . '.php';

        return $path;
    }

    /**
     * Parse the contents of mix-manifest.json and store as array.
     *
     * @since  1.0.0
     */
    private static function parse_manifest()
    {
        $manifest_path = get_stylesheet_directory() . '/dist/mix-manifest.json';

        if (\file_exists($manifest_path)) {
            $manifest = \file_get_contents($manifest_path);

            static::$manifest = (array) \json_decode($manifest);
        }
    }
}
