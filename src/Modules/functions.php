<?php

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Utils;
use Snap\Core\Router;

/**
 * A helper function for calling Snap::module.
 *
 * Render a module template from the modules directory, optionally passing data to the template.
 *
 * @since  1.0.0
 *
 * @see Snap\Core\Snap::module
 */
function snap_render_module($name, $slug = '', $data = null, $extract = false)
{
    Snap::module($name, $slug, $data, $extract);
}




/**
 * Runs the standard WP loop, and renders a module for each post.
 *
 * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
 * and allows easy module choice for each iteration.
 *
 * @since 1.0.0
 *
 * @see Snap\Core\Utils::get_user_role
 */
function snap_loop($module = null, $module_overrides = null, $wp_query = null)
{
    Snap::loop($module, $module_overrides, $wp_query);
}

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




if (! function_exists('config')) {
    /**
     * Returns a key from the Config service.
     *
     * @since  1.0.0
     *
     * @param  string $option  The option name to fetch.
     * @param  mixed  $default If the option was not found, the default value to be returned instead.
     * @return mixed The option value, or default if nto found.
     */
    function config($key, $default = null)
    {
        return Snap::services()->get('Snap\Core\Config')->get($key, $default);
    }
}




/*
 * *************************************************************************
 * Utility functions
 *
 * Some useful functions to make everyday WordPress life easier.
 * *************************************************************************
 */

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
     * Gets the path segments of the curernt URL.
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



/*
 * *************************************************************************
 * Image helpers
 *
 * A collection of functions for getting meta information about image sizes.
 * *************************************************************************
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
 * Render a view.
 *
 * If present, the child theme view folder is checked before parent theme.
 *
 * @since  1.0.0
 *
 * @param  string $slug The slug name for the generic template.
 * @param  string $name Optional. The name of the specialised template.
 *                      If no specialised template found, the generic temaplte will be loaded instead.
 */
function snap_render_view($slug, $name = '')
{
    // When Snap first boots up, it starts the output buffer. Now we have a matched view, we can flush any modules (such as the page <head>).
    ob_end_flush();
    
    get_template_part('templates/views/' . str_replace([ 'templates/views/', '.php' ], '', $slug), $name);
}







/**
 * Output snapKit pagination
 *
 * @package Core
 * @subpackage Navigation
 *
 * @param  array $args See above!
 */
function snap_pagination($args = [])
{
    $defaults = [
        'echo'                => true,
        'range'               => 5,
        'custom_query'        => false,
        'show_first_last'     => true,
        'show_previous_next'  => true,
        'active_link_wrapper' => '<li class="active">%s</li>',
        'link_wrapper'        => '<li><a href="%s">%s</a></li>',
        'first_wrapper'       => '<li><a href="%s">' . __('First page', 'snap') . '</a></li>',
        'previous_wrapper'    => '<li><a href="%s">' . __('Previous', 'snap') . '</a></li>',
        'next_wrapper'        => '<li><a href="%s">' . __('Next', 'snap') . '</a></li>',
        'last_wrapper'        => '<li><a href="%s">' . __('Last page', 'snap') . '</a></li>',
        'before_output'       => '<nav aria-label="' . __('Pagination', 'snap') . '"><ul role="navigation">',
        'after_output'        => '</ul></nav>'
    ];

    $args = wp_parse_args(
        $args,
        /**
         * Filter the default arguments.
         * Great for working with Front End Frameworks
         *
         * @param  array $defaults The default arguments
         * @return array
         */
        apply_filters('snap_pagination_defaults', $defaults)
    );


    // If a query object has not been set, use the global.
    if (! $args['custom_query']) {
        global $wp_query;
        $args['custom_query'] = $wp_query;
    }

    // Find the number of pages with a special case for WP_User_Query.
    if ($args['custom_query'] instanceof WP_User_Query) {
        $num_pages = (int) empty($args['custom_query']->get_results()) ? 0 : ceil($args['custom_query']->get_total() / $args['custom_query']->query_vars['number']);
    } else {
        $num_pages = (int) $args['custom_query']->max_num_pages;
    }

    // Get current page index.
    $current_page = empty(get_query_var('paged')) ? 1 : intval(get_query_var('paged'));

    // work out the point at which to advance the page number list
    $args['range'] = (int) $args['range'] - 1;
    $ceil = absint(ceil($args['range'] / 2));

    // bail if there arent any pages
    if ($num_pages <= 1) {
        return false;
    }

    if ($num_pages > $args['range']) {
        if ($current_page <= $args['range']) {
            $min = 1;
            $max = $args['range'] + 1;
        } elseif ($current_page >= ($num_pages - $ceil)) {
            $min = $num_pages - $args['range'];
            $max = $num_pages;
        } elseif ($current_page >= $args['range'] && $current_page < ($num_pages - $ceil)) {
            $min = $current_page - $ceil;
            $max = $current_page + $ceil;
        }
    } else {
        $min = 1;
        $max = $num_pages;
    }

    // generate navigation links
    $previous_link = esc_attr(get_pagenum_link(intval($current_page) - 1));
    $next_link = esc_attr(get_pagenum_link(intval($current_page) + 1));
    $first_page_link = esc_attr(get_pagenum_link(1));
    $last_page_link = esc_attr(get_pagenum_link($num_pages));

    // output HTML holder
    $output = '';

    // add 'first page' link
    if ($first_page_link && $current_page > 2 && $args['show_first_last']) {
        $output .= sprintf($args['first_wrapper'], $first_page_link);
    }

    // add previous page link
    if ($previous_link && $current_page !== 1 && $args['show_previous_next']) {
        $output .= sprintf($args['previous_wrapper'], $previous_link);
    }

    // add pagination links
    if (! empty($min) && ! empty($max) && $args['range'] >= 0) {
        for ($i = $min; $i <= $max; $i++) {
            if ($current_page == $i) {
                // output active html
                $output .= sprintf($args['active_link_wrapper'], $i);
            } else {
                // output link html
                $output .= sprintf(
                    $args['link_wrapper'],
                    esc_attr(get_pagenum_link($i)),
                    number_format_i18n($i)
                );
            }
        }
    }

    // output next page link
    if ($next_link && $num_pages != $current_page && $args['show_previous_next']) {
        $output .= sprintf($args['next_wrapper'], $next_link);
    }

    // output last page link
    if ($last_page_link && $args['show_first_last']) {
         $output .= sprintf($args['last_wrapper'], $last_page_link);
    }

    // apply before and after content if present in the args
    if (isset($output)) {
        $output = $args['before_output'] . $output . $args['after_output'];
    }

    /**
     * Filter snap_pagination output
     *
     * @var string $output The output HTML for the pagination
     * @return  string The filtered HTML
     */
    $output = apply_filters('snap_pagination_output', $output);

    // if $args['echo'], then print the pagination
    if ($args['echo']) {
        echo $output;
        return;
    }

    return $output;
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
