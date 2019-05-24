<?php

namespace Snap\Core;

use Exception;
use Hodl\Exceptions\ContainerException;
use Rakit\Validation\Validator;
use Snap\Exceptions\Startup_Exception;
use Snap\Http\Request;
use Snap\Http\Response;
use Snap\Http\Validation\Validation;
use Snap\Media\Image_Service;
use Snap\Templating\Templating_Interface;
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
     * @throws Startup_Exception
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

                static::initView();

                $classmap = null;
                $classmap_cache = \get_stylesheet_directory() . '/cache/config/' . \sha1(NONCE_SALT . 'classmap');

                if (WP_DEBUG === false && \file_exists($classmap_cache)) {
                    $classmap = \file_get_contents($classmap_cache);
                }

                $loader->load_theme($classmap);
            } catch (Exception $e) {
                throw new Startup_Exception($e->getMessage());
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

            static::$container->add_instance(static::$container);
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

            static::$container->add_instance($config);
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
            static::$container->resolve_method($provider, 'boot');
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
        if (!static::$container->has(Templating_Interface::class)) {
            // Add the default rendering engine.
            static::$container->add_singleton(
                \Snap\Templating\Standard\Standard_Strategy::class,
                function () {
                    return new \Snap\Templating\Standard\Standard_Strategy;
                }
            );

            static::$container->bind(\Snap\Templating\Standard\Standard_Strategy::class, Templating_Interface::class);

            static::$container->add(
                \Snap\Templating\Standard\Partial::class,
                function (Container $hodl) {
                    return $hodl->resolve(\Snap\Templating\Standard\Partial::class);
                }
            );
        }
    }

    /**
     * Add the View class ot the container.
     */
    private static function initView()
    {
        static::$container->add_singleton(
            View::class,
            function (Container $hodl) {
                return $hodl->resolve(View::class);
            }
        );

        static::$container->alias(View::class, 'view');
    }

    /**
     * Add Snap services to the container.
     */
    private static function initServices()
    {
        // Add Image service.
        static::$container->add_singleton(
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
        static::$container->add_singleton(
            Router::class,
            function () {
                return new Router();
            }
        );

        static::$container->add_singleton(
            Request::class,
            function () {
                return new Request();
            }
        );

        static::$container->add_singleton(
            Response::class,
            function (\Hodl\Container $container) {
                /** @var \Snap\Templating\View $view */
                $view = $container->get(View::class);

                return new Response($view);
            }
        );

        // This is required to fill with any custom rules.
        static::$container->add_singleton(
            Validator::class,
            function () {
                return new Validator();
            }
        );

        static::$container->add(
            Validation::class,
            function(\Hodl\Container $container) {
                $container->resolve(Validation::class);
            }
        );

        static::$container->alias(Router::class, 'router');
        static::$container->alias(Request::class, 'request');
        static::$container->alias(Response::class, 'response');
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
        static::$container->add_instance($wp_query);
        static::$container->add_instance($wpdb);
    }
}
