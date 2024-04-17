<?php

namespace Snap\Core;

use Snap\Core\Concerns\ManagesHooks;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Services\ServiceProvider;
use Snap\Utils\Str;
use Somnambulist\Components\Validation\Rule;
use Somnambulist\Components\Validation\Validator;
use Theme\Bootstrap;

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
     * @var array
     */
    private static $theme_hookables = [];

    /**
     * Cached list of scanned folders.
     *
     * @var array
     */
    private $visited = [];

    /**
     * The Snap autoloader.
     *
     * @param string $class The fully qualified class name to load.
     */
    public static function classAutoload($class): void
    {
        // If it is a Theme namespace, check the includes cache to avoid filesystem calls.
        if (isset(static::$theme_includes[$class])) {
            require static::$theme_includes[$class];
        }
    }

    /**
     * The alias autoloader.
     *
     * @param string $class The fully qualified class name to load.
     */
    public static function aliasAutoload($class): void
    {
        if (\array_key_exists($class, static::$aliases)) {
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
    public function __construct()
    {
        \spl_autoload_register(self::class . '::classAutoload', true);
        \spl_autoload_register(self::class . '::aliasAutoload', true);

        static::$aliases = Config::get('services.aliases');

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
    public function loadTheme($classmap = null): void
    {
        if ($classmap !== null) {
            static::$theme_includes = \unserialize($classmap, false);
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

            $this->initThemeProviders();
            $this->runHookables();
        }

        $this->initThemeSetup();
    }

    /**
     * Return the full list of all theme included classes.
     *
     * @return array
     */
    public function getThemeIncludes(): array
    {
        return static::$theme_includes;
    }

    /**
     * Ensures the the global Request object has access to the global $wp properties.
     */
    public function populateRequest(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        Request::populateWpParams();

        global $post;

        if ($post instanceof \WP_Post) {
            Container::addInstance($post);
        }
    }

    /**
     * Register additional Snap widgets.
     */
    private function initWidgets(): void
    {
        \add_action(
            'widgets_init',
            static function () {
                \register_widget(\Snap\Widgets\RelatedPages::class);
            }
        );
    }

    /**
     * If the class is a Hookable, initialize the class and fire the run() method.
     *
     * @param string $class_name The path to an included file.
     */
    private function initHookable(string $class_name): void
    {
        // If the included class extends the Hookable abstract.
        if (\class_exists($class_name)) {
            if (\is_subclass_of($class_name, Hookable::class)) {
                // Boot it up and resolve dependencies.
                static::$theme_hookables[] = $class_name;

                return;
            }

            if (\is_subclass_of($class_name, Rule::class)) {
                $class_parts = \explode('\\', $class_name);

                Container::get('validationFactory')->addRule(
                    Str::toSnake(\end($class_parts)),
                    Container::resolve($class_name)
                );
            }
        }
    }

    /**
     * Initialize any theme service providers.
     */
    private function initThemeProviders(): void
    {
        foreach (Config::get('services.theme_providers') as $class_name) {
            if (\is_subclass_of($class_name, ServiceProvider::class)) {
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
    private function scanDir(string $folder, array $files = []): array
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
     * Include Theme\ThemeSetup
     */
    private function initThemeSetup(): void
    {
        Container::resolve(Bootstrap::class)->run();
    }

    /**
     * Run the registered Hookables.
     */
    private function runHookables(): void
    {
        foreach (static::$theme_hookables as $hookable) {
            Container::resolve($hookable)->run();
        }
    }
}
