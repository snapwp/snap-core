<?php

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die('Direct access is forbidden.');
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
