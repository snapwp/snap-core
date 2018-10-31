<?php

namespace Snap\Core;

use Hodl\Container;
use Rakit\Validation\Validator;
use Snap\Templating\View;
use Snap\Templating\Templating_Interface;
use \Snap\Services\Image_Service;

/**
 * The main Snap class.
 *
 * Provides theme wide access to the service container and provides handy accessors for Core classes.
 *
 * @since 1.0.0
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
     * Container instance.
     *
     * @since 1.0.0
     * @var Container
     */
    private static $container;

    /**
     * Whether Snap has been setup yet.
     *
     * @since 1.0.0
     * @var boolean
     */
    private static $setup = false;

    /**
     * This class never needs to be instantiated.
     *
     * @since  1.0.0
     */
    final private function __construct()
    {
        // No code here...
    }

    /**
     * This class never needs to be instantiated.
     *
     * @since  1.0.0
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
     * @since  1.0.0
     */
    public static function setup()
    {
        add_action(
            'after_switch_theme',
            function () {
                \flush_rewrite_rules();
            }
        );

        if (! self::$setup) {
            self::create_container();
            self::init_config();

            self::$container->addSingleton(
                Router::class,
                function () {
                    return new Router();
                }
            );

            self::$container->addSingleton(
                Request::class,
                function () {
                    return new Request();
                }
            );
            

            self::$container->addSingleton(
                Validator::class,
                function () {
                    return new Validator();
                }
            );

            self::init_services();


            // Add global WP classes.
            global $wpdb;
            global $wp_query;
            self::$container->addInstance($wp_query);
            self::$container->addInstance($wpdb);

            // Run the loader.
            $loader = new Loader();
            $loader->boot();

            self::register_providers();

            // Init after the providers to allow switching of templating strategy.
            self::init_templating();
        }

        self::$setup = true;
    }

    /**
     * Create the static Container instance.
     *
     * @since 1.0.0
     */
    public static function create_container()
    {
        self::$container = new Container();
    }

    /**
     * Create a config instance, provide config directories, and add to the container.
     *
     * @since 1.0.0
     * @throws \Hodl\Exceptions\ContainerException
     */
    public static function init_config()
    {
        $config = new Config();
        $config->add_path(get_template_directory().'/config');

        if (is_child_theme()) {
            $config->add_path(get_stylesheet_directory().'/config');
        }

        self::$container->addInstance($config);
    }

    /**
     * Add the templating definitions to the container.
     *
     * Adds the View class, and if no other templating strategy is present, adds and binds the default.
     *
     * @since  1.0.0
     */
    public static function init_templating()
    {
        // If no templating strategy has already been registered.
        if (! self::$container->has(Templating_Interface::class)) {
            // Add the default rendering engine.
            self::$container->add(
                \Snap\Templating\Standard\Strategy::class,
                function () {
                    return new \Snap\Templating\Standard\Strategy;
                }
            );

            self::$container->bind(\Snap\Templating\Standard\Strategy::class, Templating_Interface::class);

            self::$container->add(
                \Snap\Templating\Standard\Partial::class,
                function (Container $hodl) {
                    return $hodl->resolve(\Snap\Templating\Standard\Partial::class);
                }
            );
        }

        self::$container->addSingleton(
            View::class,
            function (Container $hodl) {
                return $hodl->resolve(View::class);
            }
        );
    }

    /**
     * Add Snap services to the container.
     *
     * @since  1.0.0
     */
    public static function init_services()
    {
        // Add Image service.
        self::$container->addSingleton(
            Image_Service::class,
            function () {
                return new Image_Service();
            }
        );
        
        // Bind Image service to alias.
        self::$container->alias(Image_Service::class, 'image');
    }

    /**
     * Return the Container object.
     *
     * @since  1.0.0
     *
     * @return \Hodl\Container
     */
    public static function services()
    {
        return self::$container;
    }

    /**
     * Fetches the config object from the container, or optionally fetches a config option directly.
     *
     * @since  1.0.0
     *
     * @param string $option  The option name to fetch.
     * @param mixed  $default If the option was not found, the default value to be returned instead.
     * @return mixed|Config
     */
    public static function config($option = null, $default = null)
    {
        try {
            if ($option === null) {
                return self::services()->get(Config::class);
            }

            return self::services()->get(Config::class)->get($option, $default);
        } catch (\Exception $exception) {
            \error_log($exception->getMessage());
            return null;
        }
    }

    /**
     * Fetch the Router object from the container.
     *
     * @since  1.0.0
     *
     * @return Router
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     */
    public static function route()
    {
        $router = self::services()->get(Router::class);
        $router->reset();

        return $router;
    }

    /**
     * Fetch the Request object from the container.
     *
     * @since  1.0.0
     *
     * @return object The Request instance from the container.
     */
    public static function request()
    {
        try {
            return self::services()->get(Request::class);
        } catch (\Exception $exception) {
            \error_log($exception->getMessage());
            return null;
        }
    }

    /**
     * Fetch the Request object from the container.
     *
     * @since  1.0.0
     *
     * @return object The View instance from the container.
     */
    public static function view()
    {
        try {
            return self::services()->get(View::class);
        } catch (\Exception $exception) {
            \error_log($exception->getMessage());
            return null;
        }
    }

    /**
     * Registers any service providers defined in theme config.
     *
     * @since 1.0.0
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \ReflectionException
     */
    private static function register_providers()
    {
        $providers = self::config('services.providers');

        foreach ($providers as $provider) {
            $provider = self::services()->resolve($provider);
            $provider->register();
        }

        foreach ($providers as $provider) {
            self::services()->resolveMethod($provider, 'boot');
        }
    }
}
