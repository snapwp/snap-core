<?php

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Utils;
use Snap\Core\Router;
use Snap\Core\View;

/*
 * *********************************************************************************************************************
 * Templating functions
 *
 * Wrappers for Snap\Core\View methods.
 * *********************************************************************************************************************
 */

if (! function_exists('snap_render_partial')) {
    /**
     * A helper function for calling Snap::partial.
     *
     * Render a partial template from the partials directory, optionally passing data to the template.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\View::partial
     */
    function snap_render_partial($name, $slug = '', $data = null, $extract = false)
    {
        Snap::view()->partial($name, $slug, $data, $extract);
    }
}

if (! function_exists('snap_render_partial')) {
    /**
     * Render a view.
     *
     * If present, the child theme view folder is checked before parent theme.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\View::render
     */
    function snap_render_view($slug, $name = '')
    {
        Snap::view()->render($name, $slug);
    }
}

if (! function_exists('snap_loop')) {
    /**
     * Runs the standard WP loop, and renders a partial for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy partial choice for each iteration.
     *
     * @since 1.0.0
     *
     * @see Snap\Core\View::get_user_role
     */
    function snap_loop($partial = null, $partial_overrides = null, $wp_query = null)
    {
        Snap::view()->loop($partial, $partial_overrides, $wp_query);
    }
}

if (! function_exists('snap_pagination')) {
    /**
     * Output Snap pagination
     *
     * @since 1.0.0
     *
     * @see Snap\Core\View::pagination
     */
    function snap_pagination($args = [])
    {
        return Snap::view()->pagination($args);
    }
}

if (! function_exists('snap_get_current_view')) {
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

if (! function_exists('config')) {
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

if (! function_exists('snap_get_current_url')) {
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

if (! function_exists('snap_get_current_url_segments')) {
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

if (! function_exists('snap_get_widget_count')) {
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

if (! function_exists('snap_get_user_role')) {
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

if (! function_exists('snap_debug_hook')) {
    /**
     * Lists debug info about all callbacks for a given hook.
     *
     * Returns information for all callbacks in order of execution and priority.
     *
     * @since  1.0.0
     *
     * @see Snap\Core\Hookable::debug_hook
     */
    function snap_debug_hook($hook)
    {
        return Hookable::debug_hook($hook);
    }
}




/*
 * *********************************************************************************************************************
 * Image helpers
 *
 * A collection of functions for getting meta information about image sizes.
 * *********************************************************************************************************************
 */

if (! function_exists('snap_get_image_sizes')) {
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

if (! function_exists('snap_get_image_size')) {
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

if (! function_exists('snap_get_image_width')) {
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

if (! function_exists('snap_get_image_height')) {
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


































/**
 * Get value of top level page id in heirarchy
 * You can optionally pass a post id/object through to get the parent of
 *
 * Does not work with the objects returned by `get_pages()`
 *
 *
 * @param (int|WP_Post|array) $post null Optional post object/arrayor ID of a post to find the ancestors of
 * @return int ID
 */
function snap_get_top_parent_page_id($post = null)
{
    if (is_search() || is_404()) {
        return null;
    }
    
    switch ($post) {
        case null;
            global $post;
        case is_int($post):
            $post = get_post($post);
            
        case is_object($post):
            $ancestors = $post->ancestors;
            break;
        case is_array($post):
            $ancestors = $post['ancestors'];
            break;
    }
 
    // Check if page is a child page (any level)
    if ($ancestors && ! empty($ancestors)) {
        //  Grab the ID of top-level page from the tree
        return (int) end($ancestors);
    } else {         // Page is the top level, so use its own id
        return (int) $post->ID;
    }
}


/**
 * Returns an array of meta values from the DB
 *
 * Useful for providing counts of posts with a particular value, or for getting all value variations for a dropdown.
 *
 *
 * @example Get all featured image IDs for published posts:
 *          snap_get_meta_values( '_thumbnail_id', 'post', 'publish', true );
 *
 * @example Get all start dates for all the published event custom post type
 *          snap_get_meta_values( 'start_date', 'event' );
 *
 * @param  string  $key    Meta key to get values for
 * @param  string  $type   Post type to filter by. Defaults to post
 * @param  string  $status A post status to filter by. Defaults to publish
 * @param  boolean $unique Whether to only select distinct values
 * @return array           Array of meta values
 */
function snap_get_meta_values($key = '', $type = 'post', $status = 'publish', $unique = false)
{
    global $wpdb;

    if (empty($key)) {
        return;
    }

    $distinct = ( $unique ? 'DISTINCT' : '' );

    $r = $wpdb->get_col($wpdb->prepare("
        SELECT {$distinct} pm.meta_value FROM {$wpdb->postmeta} as pm
        LEFT JOIN {$wpdb->posts} as p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s' 
        AND p.post_status = '%s' 
        AND p.post_type = '%s'
    ", $key, $status, $type));

    return $r;
}



/**
 * Get current page depth.
 *
 * @global $wp_query
 *
 * @since  1.0.0
 *
 * @param int|WP_Post|null $page (Optional) Post ID or post object. Defaults to the current queried object.
 * @return integer
 */
function snap_get_page_depth($page = null)
{
    if ($page === null) {
        global $wp_query;
    
        $object = $wp_query->get_queried_object();
    } else {
        $object = get_post($page);
    }

    $parent_id  = $object->post_parent;
    $depth = 0;

    while ($parent_id > 0) {
        $page = get_page($parent_id);
        $parent_id = $page->post_parent;
        $depth++;
    }
 
    return $depth;
}
