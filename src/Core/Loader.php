<?php

namespace Snap\Core;

use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Utils\Theme_Utils;

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
     * Cached list of scanned folders.
     *
     * @since 1.0.0
     * @var array
     */
    private $visited = [];

    /**
     * List of Snap classes to autoload.
     *
     * @since 1.0.0
     * @var array
     */
    private $class_list = [
        \Snap\Bootstrap\Assets::class,
        \Snap\Bootstrap\Cleanup::class,
        \Snap\Bootstrap\Comments::class,
        \Snap\Bootstrap\I18n::class,
        \Snap\Media\Size_Manager::class,
        \Snap\Templating\Handle_Post_Templates::class,
    ];

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
     * Includes all required Snap and theme files and register the Snap autoloader.
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

        if (\is_admin() || Theme_Utils::is_wplogin()) {
            $this->class_list[] = \Snap\Admin\Whitelabel::class;
            $this->class_list[] = \Snap\Admin\Columns\Post_Template::class;
            $this->class_list[] = \Snap\Media\Admin::class;

            $this->conditional_load('admin.snap_admin_theme', 'Snap\Admin\Theme');
        } else {
            $this->class_list[] = \Snap\Http\Middleware\Is_Logged_In::class;
        }

        $this->conditional_load('theme.disable_comments', 'Snap\Admin\Disable_Comments');
        $this->conditional_load('theme.disable_customizer', 'Snap\Admin\Disable_Customizer');
        $this->conditional_load('theme.disable_tags', 'Snap\Admin\Disable_Tags');

        foreach ($this->class_list as $module) {
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

        $hookable_locations = [
            \get_template_directory() . '/theme/' . $hookables_dir,
            \get_template_directory() . '/theme/Http/Ajax',
            \get_template_directory() . '/theme/Http/Middleware',
            \get_template_directory() . '/theme/Http/Validation/Rules',
            \get_template_directory() . '/theme/Content',
            \get_template_directory() . '/theme/Events',
        ];

        if (\is_child_theme()) {
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/' . $hookables_dir;
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/Http/Ajax';
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/Http/Middleware';
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/Http/Validation/Rules';
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/Content';
            $hookable_locations[] = \get_stylesheet_directory() . '/theme/Events';
        }

        // Gather all possible Hookables.
        foreach ($hookable_locations as $dir) {
            self::$theme_includes = $this->scan_dir($dir, self::$theme_includes);
        }

        if (!empty(self::$theme_includes)) {
            foreach (self::$theme_includes as $class => $path) {
                $this->init_hookable($class);
            }
        }

        self::$theme_includes = $this->scan_dir(
            \get_stylesheet_directory() . '/theme/',
            self::$theme_includes
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
     * @return array          $files array with any discovered php files appended.
     */
    private function scan_dir($folder, $files = [])
    {
        // Ensure maximum portability.
        $folder = \trailingslashit($folder);

        if (isset($this->visited[$folder])) {
            return $files;
        }

        // Check the target exists.
        if (\is_dir($folder)) {
            // Scan the directory for files to include.
            $contents = \scandir($folder);
            $this->visited[$folder] = null;
        }

        if (!empty($contents)) {
            // go through each file, adding it to the $files list.
            foreach ($contents as $file) {
                $path = $folder . $file;

                $class = \str_replace([\get_stylesheet_directory(), \get_template_directory(), '.php'], '', $path);
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
                    $files = $this->scan_dir(\trailingslashit($path), $files);
                }
            }
        }

        return $files;
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

    /**
     * Adds a class to the list if the provided config $key is true.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param string $class
     */
    private function conditional_load(string $key, string $class)
    {
        if (Config::get($key) === true) {
            $this->class_list[] = $class;
        }
    }
}
