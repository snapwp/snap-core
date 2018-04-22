<?php

namespace Snap\Core;

/**
 * The main Snap class.
 *
 * Provides theme access to Snap\Route, options, and templating functions.
 *
 * @since 1.0.0
 */
class Snap
{
    /**
     * Router instance.
     *
     * @since 1.0.0
     *
     * @var Snap\Router
     */
    static $router = null;

    /**
     * Request instance.
     *
     * @since 1.0.0
     *
     * @var Snap\Request
     */
    static $request = null;

    /**
     * This class never needs to be instantiated.
     *
     * @since  1.0.0
     */
    private function __construct()
    {
    }

    /**
     * Gets the internal router instance.
     *
     * Although Snap\Request is not a singleton, it makes a lot of sense to only have one instance of it
     * during a normal request cycle.
     *
     * @since 1.0.0
     *
     * @return Snap\Request
     */
    private static function get_router()
    {
        if (self::$router instanceof Router) {
            return self::$router;
        } else {
            self::$router = new Router();
            return self::$router;
        }
    }

    private static function get_request()
    {
        if (self::$request instanceof Request) {
            return self::$request;
        } else {
            self::$request = new Request();
            return self::$request;
        }
    }

    /**
     * Runs the standard WP loop, and renders a module for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy module choice for each iteration.
     *
     * @since 1.0.0
     *
     * @param  string    $module    Optional. The module name to render for each post.
     *                              If null, then defaults to post-type/{post type}.php.
     * @param  array     $module    Optional. An array of overrides.
     *                              Keys are integers indicating which iteration index to apply to, and values are the module to load instead of $module
     *                              There is also a special key 'alternate' which will load the value on every odd iteration.
     * @param  WP_Query  $wp_query  Optional. An optional custom WP_Query to loop through. Defaults to the global WP_Query instance.
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
                    && isset($module_overrides[ 'alternate' ])
                    && $count % 2 !== 0
                ) {
                    // An override is present, so load that instead.
                    Snap::module($module_overrides[ 'alternate' ]);
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

            include(locate_template('modules/' . $name . ( ! empty($slug) ? '-' . $slug : '' ) . '.php'));
        } else {
            unset($data, $extract);
            get_template_part('modules/' . $name, $slug);
        }
    }

    public static function route()
    {
        self::get_router()->reset();

        return self::get_router();
    }

    public static function request()
    {
        return self::get_request();
    }
}
