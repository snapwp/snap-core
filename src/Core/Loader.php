<?php

namespace Snap\Core;

use Snap\Services\Config;
use Snap\Services\Container;

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
     * Hold all current class aliases.
     *
     * @since 1.0.0
     * @var array
     */
    private static $aliases = [];

    /**
     * The Snap autoloader.
     *
     * @since   1.0.0
     *
     * @param string $class The fully qualified class name to load.
     * @return bool
     */
    public static function class_autoload($class)
    {
        // If it is a Theme namespace, check the includes cache to avoid filesystem calls.
        if (isset(static::$theme_includes[ $class ])) {
            /** @noinspection PhpIncludeInspection */
            require static::$theme_includes[ $class ];
            return true;
        }

        return false;
    }

    /**
     * The alias autoloader.
     *
     * @since 1.0.0
     *
     * @param string $class The fully qualified class name to load.
     * @return bool|null
     */
    public static function alias_autoload($class)
    {
        if (\in_array($class, \array_keys(static::$aliases))) {
            return \class_alias(static::$aliases[ $class ], $class);
        }

        return false;
    }

    /**
     * Includes all required Snap and theme files and registers the Snap autoloader.
     *
     * Initializes any Snap\Hookable classes.
     *
     * @since 1.0.0
     *
     * @throws \Exception
     */
    public function boot()
    {
        \spl_autoload_register(__NAMESPACE__ . '\Loader::class_autoload', true);
        \spl_autoload_register(__NAMESPACE__ . '\Loader::alias_autoload', true);

        static::$aliases = Config::get('services.aliases');

        $snap_modules = [
            \Snap\Bootstrap\Assets::class,
            \Snap\Bootstrap\Cleanup::class,
            \Snap\Bootstrap\Comments::class,
            \Snap\Bootstrap\I18n::class,
            \Snap\Media\Size_Manager::class,
            \Snap\Templating\Handle_Post_Templates::class,
        ];

        if (\is_admin() || $this->is_wplogin()) {
            $snap_modules[] = \Snap\Admin\Whitelabel::class;
            $snap_modules[] = \Snap\Admin\Columns\Post_Template::class;
            $snap_modules[] = \Snap\Media\Admin::class;

            if (Config::get('admin.snap_admin_theme') === true) {
                $snap_modules[] = \Snap\Admin\Theme::class;
            }
        } else {
            $snap_modules[] = \Snap\Request\Middleware\Is_Logged_In::class;
        }

        if (Config::get('theme.disable_comments') === true) {
            $snap_modules[] = \Snap\Admin\Disable_Comments::class;
        }

        if (Config::get('theme.disable_customizer') === true) {
            $snap_modules[] = \Snap\Admin\Disable_Customizer::class;
        }

        if (Config::get('theme.disable_tags') === true) {
            $snap_modules[] = \Snap\Admin\Disable_Tags::class;
        }

        foreach ($snap_modules as $module) {
            Container::resolve($module)->run();
        }

        $this->init_widgets();

        // Now all core files are loaded, turn on output buffer until a view is dispatched.
        \ob_start();
    }

    /**
     * Load all theme files.
     *
     * @since 1.0.0
     */
    public function load_theme()
    {
        $hookables_dir = \trim(Config::get('theme.hookables_directory'), '/');

        // Populate $theme_includes.
        self::$theme_includes = $this->scan_dir(
            \get_template_directory() . '/theme/' . $hookables_dir,
            self::$theme_includes,
            \get_template_directory()
        );

        self::$theme_includes = $this->scan_dir(
            \get_stylesheet_directory() . '/theme/' . $hookables_dir,
            self::$theme_includes,
            \get_stylesheet_directory()
        );

        if (! empty(self::$theme_includes)) {
            foreach (self::$theme_includes as $class => $path) {
                $this->init_hookable($class);
            }
        }

        // Todo this is still kind of messy
        self::$theme_includes = $this->scan_dir(
            \get_stylesheet_directory() . '/theme/',
            self::$theme_includes,
            \get_stylesheet_directory()
        );

        $this->init_theme_providers();
        $this->init_theme_setup();
    }

    /**
     * Register additional Snap widgets.
     *
     * @since 1.0.0
     */
    private function init_widgets()
    {
        \add_action(
            'widgets_init',
            function () {
                \register_widget(\Snap\Widgets\Related_Pages::class);
            }
        );
    }

    /**
     * If the class is a Hookable, initialize the class and fire the run() method.
     *
     * @since 1.0.0
     *
     * @param string $class_name The path to an included file.
     */
    private function init_hookable($class_name)
    {
        // If the included class extends the Hookable abstract.
        if (\class_exists($class_name)) {
            if (\is_subclass_of($class_name, Hookable::class)) {
                // Boot it up and resolve dependencies.
                Container::resolve($class_name)->run();
                return;
            }

            if (\is_subclass_of($class_name, 'Rakit\Validation\Rule')) {
                $class_parts = \explode('\\', $class_name);

                Container::get('Rakit\Validation\Validator')->addValidator(
                    \strtolower(\end($class_parts)),
                    Container::resolve($class_name)
                );
                return;
            }
        }
    }

    /**
     * Initialize any theme service providers.
     *
     * @since 1.0.0
     */
    private function init_theme_providers()
    {
        foreach (Config::get('services.theme_providers') as $class_name) {
            if (\is_subclass_of($class_name, \Snap\Services\Service_Provider::class)) {
                $provider = Container::resolve($class_name);
                Container::resolve_method($provider, 'register');
                return;
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
     * @param  string $strip  Strip this text from the returned path.
     * @return array          $files array with any discovered php files appended.
     */
    private function scan_dir($folder, $files = [], $strip = '')
    {
        // Ensure maximum portability.
        $folder = \trailingslashit($folder);

        // Check the target exists.
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
                    $files = $this->scan_dir(\trailingslashit($path), $files, $strip);
                }
            }
        }

        return $files;
    }

    /**
     * Detect whether the current request is to the login page.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_wplogin()
    {
        $abspath = \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ABSPATH);

        $files = \get_included_files();

        if (\in_array($abspath.'wp-login.php', $files) || \in_array($abspath.'wp-register.php', $files)) {
            return true;
        }

        if (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') {
            return true;
        }

        if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']== '/wp-login.php') {
            return true;
        }

        return false;
    }

    /**
     * Include Theme\Theme_Setup
     *
     * @since 1.0.0
     */
    private function init_theme_setup()
    {
        if (isset(static::$theme_includes['Theme\Theme_Setup'])) {
            $this->init_hookable('Theme\Theme_Setup');
        }
    }
}
