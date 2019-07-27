<?php

namespace Snap\Core;

use Exception;
use Hodl\Container;
use Hodl\Exceptions\ContainerException;
use Snap\Database\TaxQuery;
use Snap\Exceptions\StartupException;
use Snap\Http\Request;
use Snap\Http\Response;
use Snap\Http\Validation\Validator;
use Snap\Media\Image_Service;
use Snap\Templating\TemplatingInterface;
use Snap\Templating\View;

/**
 * The main Snap class.
 */
class Snap
{
    /**
     * SnapWP website.
     */
    const SNAPWP_HOME = 'https://snapwp.io';

    /**
     * Current Snap version.
     */
    const VERSION = '1.0.0';

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
    public static function setup()
    {
        if (static::$setup === false) {
            try {
                static::createContainer();
                static::initConfig();
                static::initRouting();
                static::initServices();
                static::addWordpressGlobals();

                // Run the loader.
                $loader = new Loader();
                $loader->boot();

                static::registerProviders();
                static::initTemplating();

                static::initContentTypes();

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
    public static function createContainer()
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
    public static function initConfig($theme_root = null)
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
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * Registers any service providers defined in theme config.
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \ReflectionException
     */
    public static function registerProviders()
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
    private static function initTemplating()
    {
        // If no templating strategy has already been registered.
        if (!static::$container->has(TemplatingInterface::class)) {
            // Add the default rendering engine.
            static::$container->addSingleton(
                \Snap\Templating\Standard\StandardStrategy::class,
                function () {
                    return new \Snap\Templating\Standard\StandardStrategy;
                }
            );

            static::$container->bind(
                \Snap\Templating\Standard\StandardStrategy::class,
                TemplatingInterface::class
            );
        }
    }

    /**
     * Add the View class ot the container.
     */
    private static function initView()
    {
        static::$container->addSingleton(View::class, function (Container $container) {
            return $container->resolve(View::class);
        });

        static::$container->alias(View::class, 'view');
    }

    private static function initContentTypes()
    {
        static::$container->add(TaxQuery::class, function () {
            return new TaxQuery();
        });
    }

    /**
     * Add Snap services to the container.
     */
    private static function initServices()
    {
        // Add Image service.
        static::$container->addSingleton(
            Image_Service::class,
            function () {
                return new Image_Service();
            }
        );

        // Bind Image service to alias.
        static::$container->alias(Image_Service::class, 'image');
    }

    /**
     * Add Snap routing, request, and validation services to the container.
     */
    private static function initRouting()
    {
        static::$container->addSingleton(
            Router::class,
            function () {
                return new Router();
            }
        );

        static::$container->addSingleton(
            Request::class,
            function () {
                return new Request();
            }
        );

        static::$container->addSingleton(
            Response::class,
            function (Container $container) {
                return $container->resolve(Response::class);
            }
        );

        // This is required to fill with any custom rules.
        static::$container->addSingleton(
            \Rakit\Validation\Validator::class,
            function () {
                return new \Rakit\Validation\Validator();
            }
        );

        static::$container->add(
            Validator::class,
            function () {
                return new Validator();
            }
        );

        static::$container->alias(Router::class, 'router');
        static::$container->alias(Request::class, 'request');
        static::$container->alias(Response::class, 'response');
        static::$container->alias(Validator::class, 'validator');
    }

    /**
     * Add WordPress globals into container.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    private static function addWordpressGlobals()
    {
        // Add global WP classes.
        global $wpdb, $wp_query;
        static::$container->addInstance($wp_query);
        static::$container->addInstance($wpdb);
    }
}
