<?php

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Utils;
use Snap\Core\Router;
use Snap\Core\Templating\View;

/*
 * *********************************************************************************************************************
 * Templating functions
 *
 * Wrappers for Snap\Core\Templating\View methods.
 * *********************************************************************************************************************
 */

if (! \function_exists('snap_render_partial')) {
    /**
     * A helper function for calling Snap::partial.
     *
     * Render a partial template from the partials directory, optionally passing data to the template.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Templating\View::partial
     */
    function snap_render_partial($name, $slug = '', $data = null, $extract = false)
    {
        Snap::view()->partial($name, $slug, $data, $extract);
    }
}

if (! \function_exists('snap_render_partial')) {
    /**
     * Render a view.
     *
     * If present, the child theme view folder is checked before parent theme.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Templating\View::render
     */
    function snap_render_view($slug, $name = '')
    {
        Snap::view()->render($name, $slug);
    }
}

if (! \function_exists('snap_loop')) {
    /**
     * Runs the standard WP loop, and renders a partial for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy partial choice for each iteration.
     *
     * @since 1.0.0
     *
     * @see Snap\Core\Templating\View::get_user_role
     */
    function snap_loop($partial = null, $partial_overrides = null, $wp_query = null)
    {
        Snap::view()->loop($partial, $partial_overrides, $wp_query);
    }
}

if (! \function_exists('snap_pagination')) {
    /**
     * Output Snap pagination
     *
     * @since 1.0.0
     *
     * @see Snap\Core\Templating\View::pagination
     */
    function snap_pagination($args = [])
    {
        return Snap::view()->pagination($args);
    }
}

if (! \function_exists('snap_get_current_view')) {
    /**
     * Returns the current view being rendered.
     *
     * @since 1.0.0
     *
     * @see Snap\Core\Utils::get_current_view
     */
    function snap_get_current_view()
    {
        Snap::view()->get_current_view();
    }
}




/*
 * *********************************************************************************************************************
 * Utility functions
 *
 * Some useful functions to make everyday WordPress life easier.
 * *********************************************************************************************************************
 */

if (! \function_exists('config')) {
    /**
     * Returns a key from the Config service.
     *
     * @since  1.0.0
     *
     * @param  string $option  The option name to fetch.
     * @param  mixed  $default If the option was not found, the default value to be returned instead.
     * @return mixed The option value, or default if not found.
     */
    function config($key, $default = null)
    {
        return Snap::services()->get('Snap\Core\Config')->get($key, $default);
    }
}

if (! \function_exists('snap_get_current_url')) {
    /**
     * Gets the current full URL of the page with or without query strings.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_current_url
     */
    function snap_get_current_url($remove_query = false)
    {
        return Utils::get_current_url($remove_query);
    }
}

if (! \function_exists('snap_get_current_url_segments')) {
    /**
     * Gets the path segments of the current URL.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Router::get_path_segments
     */
    function snap_get_current_url_segments()
    {
        return Snap::services()->get(Router::class)->get_path_segments();
    }
}

if (! \function_exists('snap_get_widget_count')) {
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * @since 1.0.0
     *
     * @see Snap\Core\Utils::get_widget_count
     */
    function snap_get_widget_count($sidebar_id)
    {
        return Utils::get_widget_count($sidebar_id);
    }
}

if (! \function_exists('snap_get_user_role')) {
    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since 1.0.0
     *
     * @see Snap\Core\Utils::get_user_role
     */
    function snap_get_user_role($user = null)
    {
        return Utils::get_user_role($user);
    }
}

if (! \function_exists('snap_debug_hook')) {
    /**
     * Lists debug info about all callbacks for a given hook.
     *
     * Returns information for all callbacks in order of execution and priority.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::debug_hook
     */
    function snap_debug_hook($hook)
    {
        return Utils::debug_hook($hook);
    }
}

if (! \function_exists('snap_get_top_parent_page_id')) {
    /**
     * Get value of top level hierarchical post ID.
     *
     * Does not work with the objects returned by get_pages().
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_top_parent_page_id
     */
    function snap_get_top_parent_page_id($post = null)
    {
        return Utils::get_top_level_parent_id($post);
    }
}

if (! \function_exists('snap_get_page_depth')) {
    /**
     * Get current page depth.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::snap_get_page_depth
     */
    function snap_get_page_depth($page = null)
    {
        return Utils::get_page_depth($page);
    }
}





/*
 * *********************************************************************************************************************
 * Image helpers
 *
 * A collection of functions for getting meta information about image sizes.
 * *********************************************************************************************************************
 */

if (! \function_exists('snap_get_image_sizes')) {
    /**
     * Get size information for all currently registered image sizes.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_image_sizes
     */
    function snap_get_image_sizes()
    {
        return Utils::get_image_sizes();
    }
}

if (! \function_exists('snap_get_image_size')) {
    /**
     * Get size information for a specific image size.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_image_size
     */
    function snap_get_image_size($size)
    {
        return Utils::get_image_size($size);
    }
}

if (! \function_exists('snap_get_image_width')) {
    /**
     * Get the px width of a specific image size.
     *
     * Really useful for HTML img tags and schema.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_image_width
     */
    function snap_get_image_width($size)
    {
        return Utils::get_image_width($size);
    }
}

if (! \function_exists('snap_get_image_height')) {
    /**
     * Get the px height of a specific image size.
     *
     * Really useful for HTML img tags and schema.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Utils::get_image_height
     */
    function snap_get_image_height($size)
    {
        return Utils::get_image_height($size);
    }
}
