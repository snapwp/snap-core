<?php

namespace Snap\Core;

use Exception;
use Hodl\Container;
use Hodl\Exceptions\ContainerException;
use Snap\Core\Bootstrap\SnapLoader;
use Snap\Database\MediaQuery;
use Snap\Database\PostQuery;
use Snap\Database\TaxQuery;
use Snap\Exceptions\StartupException;
use Snap\Http\Request;
use Snap\Http\Response;
use Snap\Http\Validation\Validator;
use Snap\Media\ImageService;
use Snap\Routing\MiddlewareQueue;
use Snap\Routing\Router;
use Snap\Templating\Strategies\StrategyInterface;
use Snap\Templating\View;
use Snap\Utils\Email;

/**
 * The main Snap class.
 */
class Snap
{
    /**
     * SnapWP website.
     */
    public const SNAPWP_HOME = 'https://snapwp.io';

    /**
     * Current Snap version.
     */
    public const VERSION = '1.0.0';

    /**
     * Whether Snap has been setup yet.
     */
    public static bool $short_setup = false;

    /**
     * Container instance.
     */
    private static Container $container;

    /**
     * Whether Snap has been setup yet.
     */
    private static bool $setup = false;

    /**
     * This class never needs to be instantiated.
     */
    private function __construct()
    {
        // No code here...
    }

    /**
     * This class never needs to be instantiated.
     */
    private function __clone()
    {
        // No code here...
    }

    /**
     * Setup Snap.
     *
     * Must be run in order for anything to work.
     *
     * @throws StartupException
     */
    public static function setup(): void
    {
        if (static::$setup === false) {
            try {
                static::createContainer();
                static::initConfig();
                static::initRouting();
                static::initServices();
                static::addWordpressGlobals();
                static::addEmails();

                SnapLoader::getInstance(static::getContainer())->load();

                // Run the loader.
                $loader = new Loader();

                static::registerProviders();
                static::initTemplating();
                static::initDatabase();
                static::initView();

                $classmap = null;
                $classmap_cache = \get_stylesheet_directory() . '/cache/config/' . \sha1(NONCE_SALT . 'classmap');

                if (WP_DEBUG === false && \file_exists($classmap_cache)) {
                    $classmap = \file_get_contents($classmap_cache);
                }

                $loader->loadTheme($classmap);
            } catch (Exception $e) {
                throw new StartupException($e->getMessage());
            }
        }

        static::$setup = true;
    }

    /**
     * Create the static Container instance.
     *
     * @throws ContainerException
     */
    public static function createContainer(): void
    {
        if (static::$setup === false) {
            static::$container = new Container();

            static::$container->addInstance(static::$container);
        }
    }

    /**
     * Create a config instance, provide config directories, and add to the container.
     *
     * @param string|null $theme_root Used by the publish command when not the current active theme.
     *
     * @throws ContainerException
     */
    public static function initConfig(?string $theme_root = null): void
    {
        if (static::$setup === false) {
            $config = new Config();

            if ($theme_root === null) {
                $config_cache_path = \get_stylesheet_directory() . '/cache/config/' . \sha1(NONCE_SALT . 'theme');

                if (WP_DEBUG === false && \file_exists($config_cache_path)) {
                    $config->loadFromCache(\file_get_contents($config_cache_path));
                } else {
                    $config->addPath(\get_template_directory() . '/config');

                    if (\is_child_theme()) {
                        $config->addPath(\get_stylesheet_directory() . '/config');
                    }
                }
            }

            if ($theme_root !== null) {
                $config->addPath($theme_root . '/config');
            }

            static::$container->addInstance($config);
            static::$container->alias(Config::class, 'config');
        }
    }

    /**
     * Return the Container object.
     *
     * @return Container
     */
    public static function getContainer(): Container
    {
        return static::$container;
    }

    /**
     * Registers any service providers defined in theme config.
     *
     * @throws ContainerException
     * @throws \ReflectionException
     */
    public static function registerProviders(): void
    {
        $provider_instances = [];

        foreach (static::$container->get(Config::class)->get('services.providers') as $provider) {
            try {
                $provider = static::$container->resolve($provider);
                $provider->register();

                $provider_instances[] = $provider;
            } catch (Exception $e) {
                // Fail silently if in production, otherwise throw originalException.
                if (\defined('WP_DEBUG') && WP_DEBUG) {
                    throw new ContainerException($e->getMessage());
                }
            }
        }

        foreach ($provider_instances as $provider) {
            static::$container->resolveMethod($provider, 'boot');
        }
    }

    /**
     * Add the templating definitions to the container.
     *
     * Adds the View class, and if no other templating strategy is present, adds and binds the default.
     */
    private static function initTemplating(): void
    {
        // If no templating strategy has already been registered.
        if (!static::$container->has(StrategyInterface::class)) {
            static::$container->addSingleton(
                \Snap\Templating\Blade\Factory::class,
                static function (Container $container) {
                    $templates_directory = $container->get('config')->get('theme.templates_directory');

                    $factory = self::createBladeFactory(
                        \Snap\Utils\Theme::getActiveThemePath($templates_directory),
                        \Snap\Utils\Theme::getActiveThemePath($container->get('config')->get('theme.cache_directory')) . '/templates'
                    );

                    if (\is_child_theme()) {
                        $factory->addLocation(\Snap\Utils\Theme::getParentThemePath($templates_directory));
                    }

                    return $factory;
                }
            );

            static::$container->alias(\Snap\Templating\Blade\Factory::class, 'blade');

            \Snap\Templating\Blade\Factory::setComponentNamespace('\\Theme\\Components\\');

            // Add the default rendering engine.
            self::addSingleton(\Snap\Templating\Strategies\DefaultStrategy::class, true);

            static::$container->bind(
                \Snap\Templating\Strategies\DefaultStrategy::class,
                StrategyInterface::class
            );
        }
    }

    /**
     * Build the Blade view factory.
     *
     * Laravel's view components resolve their dependencies through the global Illuminate container, so a
     * private instance is set up containing only the bindings Blade needs.
     *
     * @param string $templates_path
     * @param string $cache_path
     * @return \Snap\Templating\Blade\Factory
     */
    private static function createBladeFactory(string $templates_path, string $cache_path): \Snap\Templating\Blade\Factory
    {
        $illuminate = new \Illuminate\Container\Container();
        \Illuminate\Container\Container::setInstance($illuminate);

        $files = new \Illuminate\Filesystem\Filesystem();
        $compiler = new \Snap\Templating\Blade\Compiler($files, $cache_path);

        $resolver = new \Illuminate\View\Engines\EngineResolver();
        $resolver->register('blade', static fn () => new \Illuminate\View\Engines\CompilerEngine($compiler, $files));
        $resolver->register('php', static fn () => new \Illuminate\View\Engines\PhpEngine($files));
        $resolver->register('file', static fn () => new \Illuminate\View\Engines\FileEngine($files));

        $factory = new \Snap\Templating\Blade\Factory(
            $resolver,
            new \Illuminate\View\FileViewFinder($files, [$templates_path]),
            new \Illuminate\Events\Dispatcher($illuminate)
        );

        $factory->setContainer($illuminate);

        $illuminate->instance(\Illuminate\Contracts\View\Factory::class, $factory);
        $illuminate->alias(\Illuminate\Contracts\View\Factory::class, 'view');
        $illuminate->instance(\Illuminate\View\Compilers\BladeCompiler::class, $compiler);
        $illuminate->alias(\Illuminate\View\Compilers\BladeCompiler::class, 'blade.compiler');

        // Used by class components which return an inline template from render().
        $illuminate->instance('config', new \Illuminate\Support\Fluent(['view' => ['compiled' => $cache_path]]));

        return $factory;
    }

    /**
     * Add the View class ot the container.
     */
    private static function initView(): void
    {
        self::addSingleton(View::class, true, 'view');
    }

    /**
     * Include any database classes.
     */
    private static function initDatabase(): void
    {
        self::addFactory(PostQuery::class);
        self::addFactory(TaxQuery::class);
        self::addFactory(MediaQuery::class);
    }

    /**
     * Add Snap services to the container.
     */
    private static function initServices(): void
    {
        self::addSingleton(ImageService::class, false, 'image');
    }

    /**
     * Add Snap routing, request, and validation services to the container.
     */
    private static function initRouting(): void
    {
        self::addSingleton(Router::class, false, 'router');
        self::addSingleton(Request::class, false, 'request');
        self::addSingleton(Response::class, true, 'response');
        self::addSingleton(\Somnambulist\Components\Validation\Factory::class, false, 'validationFactory');
        self::addFactory(Validator::class, 'validator');
        self::addFactory(MiddlewareQueue::class);
    }

    /**
     * Add Emails.
     */
    private static function addEmails(): void
    {
        self::addFactory(Email::class, 'email');
    }

    /**
     * Add WordPress globals into container.
     *
     * @throws ContainerException
     */
    private static function addWordpressGlobals(): void
    {
        // Add global WP classes.
        global $wpdb, $wp_query;
        static::$container->addInstance($wp_query);
        static::$container->addInstance($wpdb);
    }

    /**
     * Shorthand to add a factory definition.
     *
     * @param string    $class
     * @param string|null $alias
     */
    private static function addFactory(string $class, ?string $alias = null): void
    {
        static::$container->add(
            $class,
            static function () use ($class) {
                return new $class();
            }
        );

        if ($alias !== null) {
            static::$container->alias($class, $alias);
        }
    }

    /**
     * Shorthand to add a singleton definition.
     *
     * @param string    $class
     * @param bool      $needsResolving
     * @param string|null $alias
     */
    private static function addSingleton(string $class, bool $needsResolving = false, ?string $alias = null): void
    {
        static::$container->addSingleton(
            $class,
            static function (Container $container) use ($class, $needsResolving) {
                if ($needsResolving === true) {
                    return $container->resolve($class);
                }

                return new $class();
            }
        );

        if ($alias !== null) {
            static::$container->alias($class, $alias);
        }
    }
}
