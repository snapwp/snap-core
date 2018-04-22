<?php

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die('Direct access is forbidden.');
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
