<?php

namespace Snap\Utils;

use Snap\Services\Blade;
use Snap\Services\Config;

/**
 * Sidebar and widget utilities.
 */
class Theme
{
    /**
     * Mix-manifest.json contents stored as array.
     */
    private static ?array $manifest = null;

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     */
    public static function getCurrentUrl(bool $remove_query_params = false): string
    {
        global $wp;

        if ($remove_query_params) {
            return \trailingslashit(\home_url($wp->request));
        }

        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            return \home_url(\filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
        }

        return \home_url(\add_query_arg(null, null));
    }

    /**
     * Shortcut to get the include path relative to the active theme directory.
     */
    public static function getActiveThemePath(string $path): string
    {
        return \str_replace('\\', '/', \trailingslashit(\get_stylesheet_directory()) . ltrim($path, '/\\'));
    }

    /**
     * Shortcut to get the include path relative to the parent theme directory.
     */
    public static function getParentThemePath($path): string
    {
        return \str_replace('\\', '/', \trailingslashit(\get_template_directory()) . ltrim($path, '/\\'));
    }

    /**
     * Shortcut to get the file URL relative to the active theme directory.
     */
    public static function getActiveThemeUri(string $path): string
    {
        return \trailingslashit(\get_stylesheet_directory_uri()) . $path;
    }

    /**
     * Shortcut to get the file URL relative to the active theme directory.
     */
    public static function getParentThemeUri(string $path): string
    {
        return \trailingslashit(\get_template_directory_uri()) . $path;
    }

    /**
     * Retrieves a filename public URL with Webpack version ID if present.
     *
     * @throws \JsonException
     */
    public static function getAssetUrl(string $file): string
    {
        if (static::$manifest === null) {
            static::parseManifest();
        }

        // There was no manifest or no file present.
        if (static::$manifest === null || !isset(static::$manifest[$file])) {
            return static::getActiveThemeUri('public/') . $file;
        }

        if (is_child_theme() && file_exists(static::getParentThemePath('public/' . static::$manifest[$file]->file))) {
            return static::getParentThemeUri('public/' . static::$manifest[$file]->file);
        }

        if (Vite::isActive()) {
            return static::getActiveThemeUri('public/') . static::$manifest[$file]->file;
        }

        return static::getActiveThemeUri('public/') . static::$manifest[$file]->file;
    }

    /**
     * Get the current manifest JSON if exists.
     */
    public static function getManifest(): ?array
    {
        if (static::$manifest === null) {
            static::parseManifest();
        }

        return static::$manifest;
    }

    /**
     * Get a system agnostic stylesheet dir.
     */
    public static function getStylesheetDirectory(): string
    {
        return static::normalisePath(\get_stylesheet_directory());
    }

    /**
     * Ensure path only uses forward slashes.
     */
    public static function normalisePath(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }

    /**
     * Transforms a partial name and returns the path to the partial relative to theme root.
     */
    public static function getPartialPath(string $partial): string
    {
        try {
            $path = Blade::getFinder()->find('partials.' . static::stripExtension($partial));
        } catch (\Exception $e) {
            return static::getTemplatesPath();
        }

        return \trim(\str_replace(static::getStylesheetDirectory(), '', static::normalisePath($path)), '/');
    }

    /**
     * Returns the full include path for a given post template.
     *
     * @param string $post_template The template to get the path for.
     */
    public static function getPostTemplatePath(string $post_template): string
    {
        try {
            $path = Blade::getFinder()->find('page-templates.' . static::stripExtension($post_template));
        } catch (\Exception $e) {
            return static::getTemplatesPath();
        }

        return \trim(\str_replace(static::getStylesheetDirectory(), '', static::normalisePath($path)), '/');
    }

    /**
     * Returns the current templates directory.
     *
     * @return string
     */
    public static function getTemplatesPath(): string
    {
        return \trailingslashit(Config::get('theme.templates_directory'));
    }

    /**
     * Strips the extension from a view.
     *
     * @param string $path
     * @return string
     */
    private static function stripExtension(string $path): string
    {
        if (!Blade::getExtension($path)) {
            return \trim($path, ". \t\n\r\0\x0B");
        }

        return \trim(\str_replace(Blade::getExtension($path), '', $path), ". \t\n\r\0\x0B");
    }

    /**
     * Parse the contents of mix-manifest.json and store as array.
     *
     * @throws \JsonException When the manifest is present but corrupt.
     */
    private static function parseManifest(): void
    {
        static::$manifest = [];

        if (is_child_theme()) {
            $manifest_path = self::getParentThemePath(Config::get('assets.manifest_path'));

            if (file_exists($manifest_path)) {
                $manifest = file_get_contents($manifest_path);
                static::$manifest = (array)json_decode($manifest, false, 512, JSON_THROW_ON_ERROR);
            }
        }

        $manifest_path = self::getActiveThemePath(Config::get('assets.manifest_path'));

        if (file_exists($manifest_path)) {
            $manifest = file_get_contents($manifest_path);
            static::$manifest = array_merge(static::$manifest, (array)json_decode($manifest, false, 512, JSON_THROW_ON_ERROR));
        }
    }
}
