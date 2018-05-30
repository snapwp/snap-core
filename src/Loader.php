<?php

namespace Snap\Core;

/**
 * Initializes Snap classes and child includes.
 *
 * @since 1.0.0
 */
class Loader
{
    /**
     * A cache of the parent/child includes from the Theme folder.
     *
     * @since 1.0.0
     * @var array
     */
    private static $theme_includes = [];

    /**
     * The Snap autoloader.
     *
     * @since 1.0.0
     * 
     * @param string $class The fully qualified class name to load.
     */
    public static function autoload($class) 
    {
        // If it is a Theme namespace, check the includes cache to avoid filesystem calls.
        if (isset(self::$theme_includes[$class])) {
            require self::$theme_includes[$class];
            return;
        }
    }

    /**
     * Includes all required Snap and theme files and registeres the Snap autoloader.
     *
     * Initializes any Snap\Hookable classes.
     *
     * @since 1.0.0
     */
    public function boot()
    {
        \spl_autoload_register(__NAMESPACE__ .'\Loader::autoload');

        $snap_modules = [
            \Snap\Core\Modules\Admin::class,
            \Snap\Core\Modules\Assets::class,
            \Snap\Core\Modules\Cleanup::class,
            \Snap\Core\Modules\I18n::class,
            \Snap\Core\Modules\Post_Templates::class,
            \Snap\Core\Modules\Images::class,
        ];

        if (Snap::config('theme.disable_comments') === true) {
            $snap_modules[] = \Snap\Core\Modules\Disable_Comments::class;
        }

        foreach ($snap_modules as $module) {
            Snap::services()->resolve($module)->run();
        }

        $this->load_widgets();

        // Now all core files are loaded, turn on output buffer until a view is dispatched.
        if (\ob_get_level()) {
            \ob_start();
        }

        $this->load_theme();
    }



    private function load_theme()
    {
        // Populate $theme_includes.
        self::$theme_includes = $this->scandir(\get_template_directory() . '/theme/', self::$theme_includes, \get_template_directory());
        self::$theme_includes = $this->scandir(\get_stylesheet_directory() . '/theme/', self::$theme_includes, \get_stylesheet_directory());

        if (! empty(self::$theme_includes)) {
            foreach (self::$theme_includes as $class => $path) {
                $this->load_hookable($class);
            }
        }
    }

    /**
     * If the class is a Hookable, initialize the class and fire the run() method.
     *
     * @since 1.0.0
     *
     * @param string $class_name The path to an included file.
     */
    private function load_hookable($class_name)
    {
        // If the included class extends the Hookable abstract.
        if (\class_exists($class_name) && \is_subclass_of($class_name, Hookable::class)) {
            // Boot it up and resolve dependencies.
            Snap::services()->resolve($class_name)->run();
        }
    }

    /**
     * Register additional Snap widgets.
     *
     * @since 1.0.0
     */
    private function load_widgets()
    {
        \add_action(
            'widgets_init',
            function () {
                \register_widget(\Snap\Core\Widgets\Related_Pages::class);
            }
        );
    }

    /**
     * Scans a directory for PHP files.
     *
     * @since  1.0.0
     *
     * @param  string $folder Directory path to scan.
     * @param  array  $files  An array to append the discovered files to.
     * @param  string $strip  Strip this text from the returned path.
     * @return array          $files array with any discovered php files appended.
     */
    private function scandir($folder, $files = [], $strip = '')
    {
        // Ensure maximum portability.
        $folder = \trailingslashit($folder);

        // Check the taret exists.
        if (\is_dir($folder)) {
            // Scan the directory for files to include.
            $contents = \scandir($folder);
        }

        if (! empty($contents)) {
            // go through each file, adding it to the $files list.
            foreach ($contents as $file) {
                $path = $folder . $file;

                $class = \str_replace([$strip, '.php'], '', $path);
                $class = \trim(
                    \str_replace([ '/', 'theme' ], [ '\\', 'Theme' ], $class), 
                    '\\'
                );

                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (\pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $files[$class] = $path;
                } elseif (\is_dir($path)) {
                    // Sub directory, scan this dir as well.
                    $files = $this->scandir(\trailingslashit($path), $files, $strip);
                }
            }
        }

        return $files;
    }
}
