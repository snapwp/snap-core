<?php

namespace Snap\Core;

/**
 * Initializes Snap classes, includes child includes, and provides the options interface.
 *
 * @since 1.0.0
 */
class Loader
{
    /**
     * The Artisan options array.
     *
     * @since  1.0.0
     *
     * @var array $options {
     *      @var  bool        disable_xmlrpc          If true sets the `xmlrpc_enabled` filter to return false.
     *                                                XMLRPC is only really used these days if Jetpack is installed,
     *                                                and can otherwise be a potential security hole.
     *      @var  int         default_image_quality   The default upload quality of image media.
     *                                                Smaller numbers give smaller uploaded image sizes, but with reduced image
     *                                                quality. Setting to 100 will actually increase uploaded image size!
     *      @var  bool        remove_asset_versions   Removes the version query strings from the end of enqueued assets. Contrary to popular belief, query strings
     *                                                can actually increase lookup time and are not good cache busters.
     *      @var  bool|array  enable_thumbnails       If true, post thumbnails are available for all post types. Can also be an array of post types to
     *                                                enable thumbnails for.
     *      @var  bool        reset_image_sizes       If true, all default image sizes are removed leaving only 'full'.
     * }
     */
    private static $options = [
        'disable_xmlrpc'            => true,
        'disable_comments'          => false,
        'default_image_quality'     => 75,
        'remove_asset_versions'     => true,
        'defer_scripts'             => true,
        'defer_scripts_skip'        => [],
        'use_jquery_cdn'            => '3.2.1',
        'img_placholder_dir'        => 'assets/images/',
        'enable_thumbnails'         => [],
        'reset_image_sizes'         => false,
        'insert_image_default_size' => 'medium_large',
    ];

    /**
     * If the supplied path is of a Snap_* class, initialize the class and fire the run() method.
     *
     * @since  1.0.0
     *
     * @param  string $path The path to an included file.
     */
    public static function load_file($path)
    {
        // Require the file.
        load_template($path, true);

        $file_name = basename($path);
        
        // Check if the file was a class.
        if (strpos($file_name, 'class-') !== false) {
            $class_name =  str_replace(' ', '_', ucwords(str_replace([ 'class-', '-', '.php'], [ '', ' ', '' ], $file_name)));

            // If the included class extends the Hookable abstract.
            if (class_exists($class_name) && is_subclass_of($class_name, '\Snap\Hookable')) {
                // Boot it up.
                ( new $class_name )->run();
            }
        }
    }

    /**
     * Includes all required Snap files.
     *
     * Initializes any Snap\Hookable classes.
     *
     * @since  1.0.0
     */
    public static function load_theme()
    {
        $snap_core = get_template_directory() . '/snap/core';
        $snap_modules = get_template_directory() . '/snap/modules';
        $snap_functions = get_template_directory() . '/snap/functions';

        // Include and load core files from the parent.
        foreach (self::scandir($snap_core) as $path) {
            load_template($path, true);
        }

        // Load Snap module classes.
        foreach (self::scandir($snap_modules) as $module) {
            self::load_file($module);
        }

        // Load Snap helper functions.
        foreach (self::scandir($snap_functions) as $path) {
            load_template($path, true);
        }

        self::load_child_theme();

        // Now all files are loaded, turn on output buffer until a view is dispatched.
        ob_start();
    }

    /**
     * Returns the option value for a given key. Returns null if the key could not be found.
     *
     * @since  1.0.0
     *
     * @param  string $key The key to get the value for.
     * @return mixed|null
     */
    public static function get_option($key)
    {
        $options = self::$options;

        if (! empty($options)) {
            return isset($options[ $key ]) ? $options[ $key ] : null;
        }

        return null;
    }

    /**
     * Returns the Snap_Factory::$options array.
     *
     * @since  1.0.0
     *
     * @return array The Snap_Factory::$options array.
     */
    public static function get_options()
    {
        return self::$options;
    }

    /**
     * Sets an option in the Snap_Factory::$options array.
     *
     * @since  1.0.0
     *
     * @param string $key   The options key to replace.
     * @param mixed  $value The new value to set.
     */
    public static function set_option($key, $value)
    {
        self::$options[ $key ] = $value;
    }

    /**
     * Sets an array of options at once.
     *
     * Uses wp_parse_args to update or create option key => value pairs in Snap_Factory::$options.
     *
     * @since  1.0.0
     *
     * @param  array $options The array of option key => value pairs.
     */
    public static function set_options($options)
    {
        self::$options = wp_parse_args($options, self::$options);
    }

    /**
     * Includes any child includes.
     *
     * Initializes any Snap_Hookable classes.
     *
     * @since  1.0.0
     */
    private static function load_child_theme()
    {
        // Path to child theme includes folder.
        $child_directory = get_stylesheet_directory() . '/includes/';

        $child_includes = [];

        if (is_admin()) {
            // Get child _admin directory contents.
            $child_includes = self::scandir($child_directory . '_admin/', $child_includes);
        } else {
            // Get child _public directory contents.
            $child_includes = self::scandir($child_directory . '_public/', $child_includes);
        }

        $child_includes = self::scandir($child_directory, $child_includes);

        /**
         * Allow the child_includes to be modified before inclusion.
         *
         * @since 1.0.0
         *
         * @param array  $child_includes The array of child files.
         * @return array $child_includes
         */
        $child_includes = apply_filters('snap_theme_includes', $child_includes);

        if (! empty($child_includes)) {
            foreach ($child_includes as $file) {
                self::load_file($file);
            }
        }
    }

    /**
     * Scans a directory for PHP files.
     *
     * @since  1.0.0
     *
     * @param  string $folder Directory path to scan.
     * @param  array  $files  An array to append the discovered files to.
     * @return array          $files array with any discovered php files appended.
     */
    private static function scandir($folder, $files = [])
    {
        // Ensure maximum portability.
        $folder = trailingslashit($folder);

        // Check the taret exists.
        if (is_dir($folder)) {
            // Scan the directory for files to include.
            $contents = scandir($folder);
        }

        if (! empty($contents)) {
            // go through each file, adding it to the $files list.
            foreach ($contents as $file) {
                $path = $folder . $file;

                if ('.' === $file || '_admin' === $file || '_public' === $file || 'index.php' === $file || '..' === $file) {
                    continue;
                } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $files[] = $path;
                } elseif (is_dir($path)) {
                    // Sub directory, scan this dir as well.
                    $files = self::scandir(trailingslashit($path), $files);
                }
            }
        }

        return $files;
    }
}
