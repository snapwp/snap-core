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
        if (isset(self::$theme_includes[ $class ])) {
            require self::$theme_includes[ $class ];
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
            \Snap\Modules\Admin::class,
            \Snap\Modules\Assets::class,
            \Snap\Modules\Cleanup::class,
            \Snap\Modules\I18n::class,
            \Snap\Modules\Post_Templates::class,
            \Snap\Images\Compatability::class,
            \Snap\Images\Size_Manager::class,
            \Snap\Images\Admin::class,
            \Snap\Admin\Whitelabel::class,
        ];

        if (Snap::config('theme.disable_comments') === true) {
            $snap_modules[] = \Snap\Modules\Disable_Comments::class;
        }

        if (Snap::config('theme.disable_customizer') === true) {
            $snap_modules[] = \Snap\Modules\Disable_Customizer::class;
        }

        if (Snap::config('admin.snap_admin_theme') === true) {
            $snap_modules[] = \Snap\Admin\Theme::class;
        }

        foreach ($snap_modules as $module) {
            Snap::services()->resolve($module)->run();
        }

        $this->load_widgets();

        $this->load_theme();
    }

    /**
     * Load all theme files.
     *
     * @since 1.0.0
     */
    private function load_theme()
    {
        // Populate $theme_includes.
        self::$theme_includes = $this->scandir(
            \get_template_directory() . '/theme/',
            self::$theme_includes,
            \get_template_directory()
        );

        self::$theme_includes = $this->scandir(
            \get_stylesheet_directory() . '/theme/',
            self::$theme_includes,
            \get_stylesheet_directory()
        );

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
        if (\class_exists($class_name)) {
            if (\is_subclass_of($class_name, Hookable::class)) {
                // Boot it up and resolve dependencies.
                Snap::services()->resolve($class_name)->run();
                return;
            }

            if (\is_subclass_of($class_name, \Snap\Services\Provider::class)) {
                $providers = Snap::config('services.providers');
                $providers[] = $class_name;
                Snap::config()->set('services.providers', $providers);
                return;
            }

            if (\is_subclass_of($class_name, 'Rakit\Validation\Rule')) {
                $class_parts = \explode('\\', $class_name);

                Snap::services()->get('Rakit\Validation\Validator')->addValidator(
                    \strtolower(\end($class_parts)),
                    Snap::services()->resolve($class_name)
                );
                return;
            }
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
                \register_widget(\Snap\Widgets\Related_Pages::class);
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
                    \str_replace(['/', 'theme'], ['\\', 'Theme'], $class),
                    '\\'
                );

                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (\pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $files[ $class ] = $path;
                } elseif (\is_dir($path)) {
                    // Sub directory, scan this dir as well.
                    $files = $this->scandir(\trailingslashit($path), $files, $strip);
                }
            }
        }

        return $files;
    }
}
