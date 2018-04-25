<?php

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Utils;

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

/**
 * Gets the current full URL of the page with querystrings, host, and scheme.
 *
 * @since  1.0.0
 *
 * @see Snap\Core\Utils::get_current_url
 */
function snap_get_current_url($remove_query = false)
{
    return Utils::get_current_url($remove_query);
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
 * Image related theme functions and helpers.
 *
 * Includeds functions to easily get registered image sizes,
 * and replacements for post_thumbnail functions with an automatic placeholder image fallback.
 *
 * @package Snap\Functions
 * @version  1.0.0
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die('Direct access is forbidden.');
}

/**
 * Get size information for all currently registered image sizes.
 *
 * @global $_wp_additional_image_sizes
 *
 * @since  1.0.0
 *
 * @return array Data for all currently registered image sizes.
 */
function snap_get_image_sizes()
{
    global $_wp_additional_image_sizes;

    $sizes = [];

    foreach (get_intermediate_image_sizes() as $size) {
        if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
            $sizes[ $size ] = [
                'width' => get_option("{$size}_size_w"),
                'height' => get_option("{$size}_size_h"),
                'crop' => (bool) get_option("{$size}_crop"),
            ];
        } elseif (isset($_wp_additional_image_sizes[ $size ])) {
            $sizes[ $size ] = [
                'width'  => $_wp_additional_image_sizes[ $size ]['width'],
                'height' => $_wp_additional_image_sizes[ $size ]['height'],
                'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
            ];
        }
    }

    return $sizes;
}


/**
 * Get size information for a specific image size.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|array Size data about an image size or false if the size doesn't exist.
 */
function snap_get_image_size($size)
{
    $sizes = snap_get_image_sizes();

    if (is_string($size) && isset($sizes[ $size ])) {
        return $sizes[ $size ];
    }

    return false;
}


/**
 * Get the width of a specific image size.
 *
 * Really useful for HTML img tags.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|int Width of an image size or false if the size doesn't exist.
 */
function snap_get_image_width($size)
{
    // Get the size meta array.
    $size = snap_get_image_size($size);

    if ($size !== false) {
        return false;
    }

    if (isset($size['width'])) {
        return (int) $size['width'];
    }

    return false;
}


/**
 * Get the height of a specific image size.
 *
 * Really useful for HTML img tags.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|int Height of an image size or false if the size doesn't exist.
 */
function snap_get_image_height($size)
{
    // Get the size meta array.
    $size = snap_get_image_size($size);

    if ($size !== false) {
        return false;
    }

    if (isset($size['height'])) {
        return (int) $size['height'];
    }

    return false;
}


/**
 * Renders a post thumbnail to the page.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  string $size The registered size of image to find
 * @param  array  $args Array of attrs => values to apply to the image
 */
function snap_the_post_thumbnail($size = 'full', $args = [])
{
    echo snap_get_the_post_thumbnail(get_the_id(), $size, $args);
}


/**
 * Returns the post thumbnail img element.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  int|object $post  The post ID or object to find the image for
 * @param  string     $size  The registered size of image to find
 * @param  array      $args  Array of attrs => values to apply to the image
 * @return string The image HTML
 */
function snap_get_the_post_thumbnail($post, $size = 'full', $args = [])
{
    // If the size provided doesn't exist, default to full.
    if (is_string($size) && snap_get_image_size($size) === false) {
        $size = 'full';
    }

    // Check we have a post thumbnail.
    if (has_post_thumbnail($post)) {
        return get_the_post_thumbnail($post, $size, $args);
    }

    $placeholder_src = snap_get_the_post_thumbnail_url($post, $size);

    if ($placeholder_src === false) {
        return false;
    }

    $output = sprintf(
        '<img src="%s" alt="%s" width="%s" height="%s" %s>',
        $placeholder_src,
        get_the_title($post),
        snap_get_image_width($size),
        snap_get_image_height($size),
        ( isset($args['class']) ? 'class="' . $args['class'] . '"' : '' )
    );

    /**
     * Filter the placeholder image HTML.
     *
     * @since  1.0.0
     *
     * @param string $output The HTML output for the placeholder image tag.
     * @return string $output The HTML output for the placeholder image tag.
     */
    return apply_filters('snap_placeholder_img_html', $output);
}


/**
 * Returns the post thumbnail URL.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  int|object $post  The post ID or object to find the image for.
 * @param  string     $size  The registered size of image to find.
 * @return string|bool The image URL or false if none found.
 */
function snap_get_the_post_thumbnail_url($post, $size = 'full')
{
    // If the size provided doesn't exist, default to full.
    if (snap_get_image_size($size) === false) {
        $size = 'full';
    }

    // Check we have a post thumbnail.
    if (has_post_thumbnail($post)) {
        return get_the_post_thumbnail_url($post, $size);
    }

    /**
     * The file extensions to search for when looking for placeholder fallback images.
     *
     * @since  1.0.0
     *
     * @param array $extensions The file extension list, in order of search preference.
     * @return array $extensions The modified file extension list.
     */
    $extensions = apply_filters('snap_placeholder_img_extensions', [ '.jpg', '.svg', '.png' ]);

    // Get the theme defined placeholder image directory path.
    $placeholder_directory = trailingslashit(Snap::config('img_placholder_dir'));

    // Get relative path to placeholder folder.
    $base = $placeholder_directory . 'placeholder-' . $size;

    // look for each extension.
    foreach ($extensions as $ext) {
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;

        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }
    }

    // If no size img found, search for generic.
    $base = $placeholder_directory . 'placeholder-' . get_post_type($post);

    foreach ($extensions as $ext) {
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;

        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }

        $path = false;
    }
    
    // If no specific img found, search for the generic placeholder.
    $base = $placeholder_directory . 'placeholder';

    foreach ($extensions as $ext) {
        // check if the file exists
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;
        
        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }
    }

    return false;
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
