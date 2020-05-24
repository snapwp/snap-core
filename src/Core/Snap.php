<?php

namespace Snap\Core;

use Exception;
use Hodl\Container;
use Hodl\Exceptions\ContainerException;
use Snap\Core\Bootstrap\SnapLoader;
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
     *
     * @var boolean
     */
    public static $short_setup = false;

    /**
     * Container instance.
     *
     * @var Container
     */
    private static $container;

    /**
     * Whether Snap has been setup yet.
     *
     * @var boolean
     */
    private static $setup = false;

    /**
     * This class never needs to be instantiated.
     */
    final private function __construct()
    {
        // No code here...
    }

    /**
     * This class never needs to be instantiated.
     */
    final private function __clone()
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
     * @throws \Hodl\Exceptions\ContainerException
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
     * @param string $theme_root Used by the publish command when not the current active theme.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    public static function initConfig($theme_root = null): void
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
     * @throws \Hodl\Exceptions\ContainerException
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
                    return new \Snap\Templating\Blade\Factory(
                        \Snap\Utils\Theme::getActiveThemePath($container->get('config')->get('theme.templates_directory')),
                        \Snap\Utils\Theme::getActiveThemePath($container->get('config')->get('theme.cache_directory')) . '/templates'
                    );
                }
            );

            static::$container->alias(\Snap\Templating\Blade\Factory::class, 'blade');

            // Add the default rendering engine.
            static::$container->addSingleton(
                \Snap\Templating\Strategies\DefaultStrategy::class,
                static function (Container $container) {
                    return $container->resolve(\Snap\Templating\Strategies\DefaultStrategy::class);
                }
            );

            static::$container->bind(
                \Snap\Templating\Strategies\DefaultStrategy::class,
                StrategyInterface::class
            );
        }
    }

    /**
     * Add the View class ot the container.
     */
    private static function initView(): void
    {
        static::$container->addSingleton(
            View::class,
            static function (Container $container) {
                return $container->resolve(View::class);
            }
        );

        static::$container->alias(View::class, 'view');
    }

    /**
     * Include any database classes.
     */
    private static function initDatabase(): void
    {
        static::$container->add(
            TaxQuery::class,
            static function () {
                return new TaxQuery();
            }
        );

        static::$container->add(
            PostQuery::class,
            static function () {
                return new PostQuery();
            }
        );
    }

    /**
     * Add Snap services to the container.
     */
    private static function initServices(): void
    {
        // Add Image service.
        static::$container->addSingleton(
            ImageService::class,
            static function () {
                return new ImageService();
            }
        );

        // Bind Image service to alias.
        static::$container->alias(ImageService::class, 'image');
    }

    /**
     * Add Snap routing, request, and validation services to the container.
     */
    private static function initRouting(): void
    {
        static::$container->addSingleton(
            Router::class,
            static function () {
                return new Router();
            }
        );

        static::$container->addSingleton(
            Request::class,
            static function () {
                return new Request();
            }
        );

        static::$container->addSingleton(
            Response::class,
            static function (Container $container) {
                return $container->resolve(Response::class);
            }
        );

        // This is required to fill with any custom rules.
        static::$container->addSingleton(
            \Rakit\Validation\Validator::class,
            static function () {
                return new \Rakit\Validation\Validator();
            }
        );

        static::$container->add(
            Validator::class,
            static function () {
                return new Validator();
            }
        );

        static::$container->add(
            MiddlewareQueue::class,
            static function () {
                return new MiddlewareQueue();
            }
        );

        static::$container->alias(Router::class, 'router');
        static::$container->alias(Request::class, 'request');
        static::$container->alias(Response::class, 'response');
        static::$container->alias(Validator::class, 'validator');
    }

    /**
     * Add Emails.
     */
    private static function addEmails(): void
    {
        static::$container->add(
            Email::class,
            static function () {
                return new Email();
            }
        );

        static::$container->alias(Email::class, 'email');
    }

    /**
     * Add WordPress globals into container.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    private static function addWordpressGlobals(): void
    {
        // Add global WP classes.
        global $wpdb, $wp_query;
        static::$container->addInstance($wp_query);
        static::$container->addInstance($wpdb);
    }
}
