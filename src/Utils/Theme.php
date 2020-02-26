<?php

namespace Snap\Utils;

use Snap\Services\Config;
use Snap\Services\Request;

/**
 * Sidebar and widget utilities.
 */
class Theme
{
    /**
     * Mix-manifest.json contents stored as array.
     *
     * @var array|null
     */
    private static $manifest = null;

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @param  boolean $remove_query If true, the URL is returned without any query params.
     * @return string The current URL.
     */
    public static function getCurrentUrl($remove_query = false)
    {
        global $wp;

        if ($remove_query === true) {
            return \trailingslashit(\home_url($wp->request));
        }

        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            return \home_url(\filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
        }

        return \home_url(\add_query_arg(null, null));
    }

    /**
     * Shortcut to get the include path relative to the active theme directory.
     *
     * @param string $path Path to append relative to active theme.
     * @return string
     */
    public static function getActiveThemePath($path)
    {
        return \str_replace('\\', '/', \trailingslashit(\get_stylesheet_directory()) . $path);
    }

    /**
     * Shortcut to get the file URL relative to the active theme directory.
     *
     * @param string $path Path to append relative to active theme directory URL.
     * @return string
     */
    public static function getActiveThemeUri($path)
    {
        return \trailingslashit(\get_stylesheet_directory_uri()) . $path;
    }

    /**
     * Retrieves a filename public URL with Webpack version ID if present.
     *
     * @param  string $file The asset file to look for.
     * @return string The (possibly versioned) asset URL.
     */
    public static function getAssetUrl($file)
    {
        if (static::$manifest === null) {
            static::parseManifest();
        }

        // There was no manifest or no file present.
        if (static::$manifest === null || !isset(static::$manifest[$file])) {
            return static::getActiveThemeUri('public/') . \ltrim($file, '/');
        }

        return static::getActiveThemeUri('public/') . \ltrim(static::$manifest[$file], '/');
    }

    /**
     * Transforms a partial name and returns the path to the partial relative to theme root.
     *
     * @param  string $partial The partial name.
     * @return string
     */
    public static function getPartialPath($partial): string
    {
        $partial = \str_replace(
            ['.php', '.'],
            ['', '/'],
            $partial
        );

        $path = static::getTemplatesPath() . 'partials/' . $partial . '.php';

        return $path;
    }

    /**
     * Returns the full include path for a given post template.
     *
     * @param string $post_template The template to get the path for.
     * @return string
     */
    public static function getPostTemplatesPath($post_template): string
    {
        $template = \str_replace(
            ['.blade.php', '.php', '.'],
            ['', '', '/'],
            $post_template
        );

        return static::getTemplatesPath() . "views/post-templates/{$template}.blade.php";
    }

    public static function getTemplatesPath(): string
    {
        return \trailingslashit(Config::get('theme.templates_directory'));
    }

    /**
     * Parse the contents of mix-manifest.json and store as array.
     */
    private static function parseManifest()
    {
        $manifest_path = \get_stylesheet_directory() . '/public/mix-manifest.json';

        if (\file_exists($manifest_path)) {
            $manifest = \file_get_contents($manifest_path);

            static::$manifest = (array)\json_decode($manifest);
        }
    }
}
