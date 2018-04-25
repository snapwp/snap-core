<?php

namespace Snap\Core;

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
     * Container instance.
     *
     * @since 1.0.0
     *
     * @var Snap\Core\Container|null
     */
    static $container = null;

    /**
     * Whether Snap has been setup yet.
     *
     * @since 1.0.0
     * 
     * @var boolean
     */
    static $setup = false;

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
        if (!self::$setup) {
            self::$container = new Container();

            self::$container->add('Router', function() {
                return new Router();
            }); 

            self::$container->add('Request', function() {
                return new Request();
            });

            // Boot up the config parser.
            $config = new Config(get_stylesheet_directory().'/config');

            self::$container->add('Config', function() use ($config) {
                return $config;
            });

            // Run the loader.
            Loader::load_theme();
        }

        self::$setup = true;
    }


    /**
     * Runs the standard WP loop, and renders a module for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy module choice for each iteration.
     *
     * @since 1.0.0
     *
     * @param  string   $module   Optional. The module name to render for each post.
     *                            If null, then defaults to post-type/{post type}.php.
     * @param  array    $module   Optional. An array of overrides.
     *                            Keys are the iteration index to apply the override, and values are the module to load instead of $module.
     *                            There is also a special key 'alternate' which will load the value on every odd iteration.
     * @param  WP_Query $wp_query Optional. An optional custom WP_Query to loop through. Defaults to the global WP_Query instance.
     */
    public static function loop($module = null, $module_overrides = null, $wp_query = null)
    {
        // Use either the global or supplied WP_Query object.
        if ($wp_query instanceof \WP_Query) {
            $wp_query = $wp_query;
        } else {
            global $wp_query;
        }

        $count = 0;

        // Render normal loop using our $wp_query value.
        if ($wp_query->have_posts()) {
            while ($wp_query->have_posts()) {
                $wp_query->the_post();

                // Work out what module to render.
                if (is_array($module_overrides) && isset($module_overrides[ $count ])) {
                    // An override is present, so load that instead.
                    Snap::module($module_overrides[ $count ]);
                } elseif (is_array($module_overrides)
                    && isset($module_overrides['alternate'])
                    && $count % 2 !== 0
                ) {
                    // An override is present, so load that instead.
                    Snap::module($module_overrides['alternate']);
                } elseif ($module === null) {
                    // Load the default module for this content type.
                    Snap::module('post-type/' . get_post_type());
                } else {
                    // Load the supplied default module.
                    Snap::module($module);
                }

                $count++;
            }
        } else {
            Snap::module('post-type/none');
        }

        wp_reset_postdata();
    }

    /**
     * Fetch and display a template module.
     *
     * @since  1.0.0
     *
     * @param  string $slug     The name for the generic template.
     * @param  string $name     Optional. The name of the specialised template.
     * @param  mixed  $data     Additional data to pass to a module. Available in the module as $data. Useful for PHP loops.
     *                          It is important to note that nothing is done to destroy/restore the current wordpress loop.
     * @param  bool   $extract  Whether to extract() $data or not.
     */
    public static function module($name, $slug = '', $data = null, $extract = false)
    {
        if ($data !== null) {
            if (is_array($data) && $extract === true) {
                extract($data);
            }

            include(locate_template('templates/modules/' . $name . ( ! empty($slug) ? '-' . $slug : '' ) . '.php'));
        } else {
            unset($data, $extract);
            get_template_part('templates/modules/' . $name, $slug);
        }
    }

    /**
     * Return the Container object.
     *
     * @since  1.0.0
     * 
     * @return Snap\Core\Container
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
     * @param string $option The option name to fetch.
     * @param mixed $default If the option was not found, the default value to be returned instead.
     * @return Snap\Core\Request
     */
    public static function config($option = null, $default = null)
    {
        if ($option === null) {
            return self::services()->get('Config');
        }

        return self::services()->get('Config')->get($option, $default);
    }

    /**
     * Fetch the Router object from the container.
     *
     * @since  1.0.0
     * 
     * @return Snap\Core\Router
     */
    public static function route()
    {
        $router = self::services()->get('Router');
        $router->reset();

        return $router;
    }

    /**
     * Fetch the Request object from the container.
     *
     * @since  1.0.0
     * 
     * @return Snap\Core\Request
     */
    public static function request()
    {
        return self::services()->get('Request');
    }
}
