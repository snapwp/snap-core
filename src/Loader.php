<?php

namespace Snap\Core;

/**
 * Initializes Snap classe and child includes.
 *
 * @since 1.0.0
 */
class Loader
{
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

        $class_name = '\\Theme\\'.ucwords(str_replace(['.php'], [''], $file_name));

        // If the included class extends the Hookable abstract.
        if (class_exists($class_name) && is_subclass_of($class_name, Hookable::class)) {
            // Boot it up and resolve dependencies
            Snap::services()->resolve($class_name)->run();
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
        $snap_modules = [
            \Snap\Core\Modules\Cleanup::class,
            \Snap\Core\Modules\Post_Templates::class,
            \Snap\Core\Modules\Images::class,
        ];

        foreach ($snap_modules as $module) {
            Snap::services()->resolve($module)->run();
        }

        self::load_child_theme();

        // Now all files are loaded, turn on output buffer until a view is dispatched.
        ob_start();
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
