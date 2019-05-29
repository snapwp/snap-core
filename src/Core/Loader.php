<?php

namespace Snap\Core;

use Snap\Core\Concerns\ManagesHooks;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Utils\Str;

/**
 * Initializes Snap classes and child includes.
 */
class Loader
{
    use ManagesHooks;

    /**
     * A cache of the parent/child includes from the Theme folder.
     *
     * @var array
     */
    private static $theme_includes = [];

    /**
     * Hold all current class aliases.
     *
     * @var array
     */
    private static $aliases = [];

    /**
     * Cached list of scanned folders.
     *
     * @var array
     */
    private $visited = [];

    /**
     * List of Snap classes to autoload.
     *
     * @var array
     */
    private $class_list = [
        \Snap\Bootstrap\Assets::class,
        \Snap\Bootstrap\Cleanup::class,
        \Snap\Bootstrap\Comments::class,
        \Snap\Bootstrap\I18n::class,
        \Snap\Media\Size_Manager::class,
        \Snap\Templating\Handle_Post_Templates::class,
        \Snap\Http\Validation\Rules\Nonce::class,
    ];

    /**
     * The Snap autoloader.
     *
     * @param string $class The fully qualified class name to load.
     */
    public static function classAutoload($class)
    {
        // If it is a Theme namespace, check the includes cache to avoid filesystem calls.
        if (isset(static::$theme_includes[$class])) {
            /** @noinspection PhpIncludeInspection */
            require static::$theme_includes[$class];
        }
    }

    /**
     * The alias autoloader.
     *
     * @param string $class The fully qualified class name to load.
     */
    public static function aliasAutoload($class)
    {
        if (\in_array($class, \array_keys(static::$aliases))) {
            \class_alias(static::$aliases[$class], $class);
        }
    }

    /**
     * Includes all required Snap and theme files and register the Snap autoloader.
     *
     * Initializes any Snap\Hookable classes.
     *
     * @throws \Exception
     */
    public function boot()
    {
        \spl_autoload_register(__NAMESPACE__ . '\Loader::classAutoload', true);
        \spl_autoload_register(__NAMESPACE__ . '\Loader::aliasAutoload', true);

        static::$aliases = Config::get('services.aliases');

        $this->loadSnapHookables();

        $this->initWidgets();

        // Ensure Request if populated.
        $this->addAction('wp', 'populateRequest');

        // Now all core files are loaded, turn on output buffer until a view is dispatched.
        \ob_start();
    }

    /**
     * Load all theme files.
     *
     * @param null|string $classmap Cached classmap.
     */
    public function loadTheme($classmap = null)
    {
        if ($classmap !== null) {
            static::$theme_includes = \unserialize($classmap);
        } else {
            $hookables_dir = \trim(Config::get('theme.hookables_directory'), '/');
            $root = \trailingslashit(\get_template_directory());

            $hookable_locations = [
                $root . 'theme/' . $hookables_dir,
                $root . 'theme/Http/Ajax',
                $root . 'theme/Http/Middleware',
                $root . 'theme/Http/Validation/Rules',
                $root . 'theme/Content',
                $root . 'theme/Events',
            ];

            if (\is_child_theme()) {
                $root = \trailingslashit(\get_stylesheet_directory());

                $hookable_locations[] = $root . 'theme/' . $hookables_dir;
                $hookable_locations[] = $root . 'theme/Http/Ajax';
                $hookable_locations[] = $root . 'theme/Http/Middleware';
                $hookable_locations[] = $root . 'theme/Http/Validation/Rules';
                $hookable_locations[] = $root . 'theme/Content';
                $hookable_locations[] = $root . 'theme/Events';
            }

            // Gather all possible Hookables.
            foreach ($hookable_locations as $dir) {
                static::$theme_includes = $this->scanDir($dir, static::$theme_includes);
            }

            if (!empty(static::$theme_includes)) {
                foreach (static::$theme_includes as $class => $path) {
                    $this->initHookable($class);
                }
            }

            static::$theme_includes = $this->scanDir(
                \get_stylesheet_directory() . '/theme/',
                static::$theme_includes
            );
        }

        $this->initThemeProviders();
        $this->initThemeSetup();
    }

    /**
     * Return the full list of all theme included classes.
     *
     * @return array
     */
    public function getThemeIncludes()
    {
        return static::$theme_includes;
    }

    /**
     * Ensures the the global Request object has access to the global $wp properties.
     */
    public function populateRequest()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        Request::populateWpParams();
    }

    /**
     * Register additional Snap widgets.
     */
    private function initWidgets()
    {
        \add_action(
            'widgets_init',
            function () {
                \register_widget(\Snap\Widgets\RelatedPages::class);
            }
        );
    }

    /**
     * If the class is a Hookable, initialize the class and fire the run() method.
     *
     * @param string $class_name The path to an included file.
     */
    private function initHookable($class_name)
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
                    Str::toSnake(\end($class_parts)),
                    Container::resolve($class_name)
                );
            }
        }
    }

    /**
     * Initialize any theme service providers.
     */
    private function initThemeProviders()
    {
        foreach (Config::get('services.theme_providers') as $class_name) {
            if (\is_subclass_of($class_name, \Snap\Services\Service_Provider::class)) {
                $provider = Container::resolve($class_name);
                Container::resolveMethod($provider, 'register');
                return;
            }
        }
    }

    /**
     * Scans a directory for PHP files.
     *
     * @param  string $folder Directory path to scan.
     * @param  array  $files  An array to append the discovered files to.
     * @return array          $files array with any discovered php files appended.
     */
    private function scanDir($folder, $files = [])
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
                    $files[$class] = $path;
                } elseif (\is_dir($path)) {
                    // Sub directory, scan this dir as well.
                    $files = $this->scanDir(\trailingslashit($path), $files);
                }
            }
        }

        return $files;
    }

    /**
     * Include Theme\Theme_Setup
     */
    private function initThemeSetup()
    {
        if (isset(static::$theme_includes['Theme\Theme_Setup'])) {
            $this->initHookable('Theme\Theme_Setup');
        }
    }

    /**
     * Adds a class to the list if the provided config $key is true.
     *
     * @param string $key
     * @param string $class
     */
    private function conditionalLoad(string $key, string $class)
    {
        if (Config::get($key) === true) {
            $this->class_list[] = $class;
        }
    }

    private function loadSnapHookables()
    {
        if (\is_admin() || Request::isLoginPage()) {
            $this->class_list[] = \Snap\Admin\Whitelabel::class;
            $this->class_list[] = \Snap\Admin\Columns\PostTemplate::class;
            $this->class_list[] = \Snap\Media\Admin::class;

            $this->conditionalLoad('admin.snap_admin_theme', 'Snap\Admin\Theme');
        } else {
            $this->class_list[] = \Snap\Http\Middleware\IsLoggedIn::class;
        }

        $this->conditionalLoad('theme.disable_comments', 'Snap\Admin\DisableComments');
        $this->conditionalLoad('theme.disable_customizer', 'Snap\Admin\DisableCustomizer');
        $this->conditionalLoad('theme.disable_tags', 'Snap\Admin\DisableTags');

        foreach ($this->class_list as $module) {
            $this->initHookable($module);
        }
    }
}
