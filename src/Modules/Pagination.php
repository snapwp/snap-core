<?php

namespace Snap\Core\Modules;

use WP_Query;
use WP_User_Query;

/**
 * A simple wrapper to output paginated links at the bottom of Query loops.
 *
 * @since  1.0.0
 */
class Pagination
{
	/**
	 * Pagination arguments array.
	 *
	 * @since  1.0.0
	 * @var array
	 */
    private $args = [];

    /**
     * The current query to paginate.
     *
     * @since  1.0.0
     * @var WP_Query|WP_User_Query|null
     */
    private $wp_query = null;

    /**
     * Total number of pages in the query.
     *
     * @since  1.0.0
     * @var integer
     */	
    private $page_count = 0;

    /**
     * The current page number.
     *
     * @since  1.0.0
     * @var integer
     */
    private $current_page = 1;

    /**
     * Create $args array and assign object properties.
     *
     * @since  1.0.0
     * 
     * @param array $args {
	 *     Optional. An array of arguments.
	 *
	 *     @type bool   $echo  		     	 Whether to echo or return the HTML. Default true.
	 *     @type int    $range 		     	 How many page links should be displayed at once. Default 5.
	 *     @type object $custom_query    	 The query to be used instead of the global WP_Query. Default false.
	 *           					     	 Also accepts WP_User_Query objects.
	 *     @type bool   $show_first_last     Whether to show first/last page links. Default true.
	 *     @type bool   $show_previous_next  Whether to show previous/next page links. Default true.
	 *     @type string $before_output		 Opening pagination HTML. 
	 *           							 Default '<nav aria-label="' . __('Pagination', 'snap') . '"><ul role="navigation">'
	 *     @type string $after_output		 Closing pagination HTML. Default '</ul></nav>'
	 *     @type string $active_link_wrapper sprintf() wrapper for the active link. Default '<li class="active">%s</li>'.
	 *     @type string $link_wrapper 		 sprintf() wrapper for non active link. Default '<li><a href="%s">%s</a></li>'.
	 *     @type string $next_wrapper	 	 sprintf() wrapper for 'next page' link. 
	 *           						     Default '<li><a href="%s">' . __('Next', 'snap') . '</a></li>'.
	 *     @type string $previous_wrapper	 sprintf() wrapper for 'previous page' link. 
	 *           						     Default '<li><a href="%s">' . __('Previous', 'snap') . '</a></li>'.
	 *     @type string $first_wrapper 		 sprintf() wrapper for 'first page' link. 
	 *           						     Default '<li><a href="%s">' . __('First page', 'snap') . '</a></li>'.
	 *     @type string $last_wrapper	 	 sprintf() wrapper for 'last page' link. 
	 *           						     Default '<li><a href="%s">' . __('Last page', 'snap') . '</a></li>'.
	 * }
     * @param WP_Query $global_query Global WP_Query instance.
     */
    public function __construct($args = [], WP_Query $global_query)
    {
    	$this->args = wp_parse_args(
	        $this->args,
	        /**
	         * Filter the default arguments.
	         * 
	         * @since  1.0.0
	         * 
	         * @param  array $defaults The default arguments.
	         * @return array
	         */
	        apply_filters('snap_pagination_defaults', $this->get_defaults())
	    );

    	if ($this->args['custom_query'] !== false) {
	        $this->wp_query = $this->args['custom_query'];
	    } else {
	        $this->wp_query = $global_query;
	    }

	    $this->set_page_count();
	    $this->set_current_page();
	    $this->set_ranges();
    }

    /**
     * Output pagination HTML.
     *
     * @since  1.0.0
     */
    public function render()
    {
    	echo $this->get();
    }

    /**
     * Return pagination HTML.
     *
     * @since  1.0.0
     * 
     * @return string Pagination HTML.
     */
    public function get()
    {
    	if ($this->page_count <= 1) {
	        return '';
	    }

	    // Output HTML holder.
	    $output = '';

	    $output .= $this->get_opening_links();
	    $output .= $this->get_links();
	    $output .= $this->get_closing_links();

	    // Apply before and after content if present in the args.
	    if (! empty($output)) {
	        $output = $this->args['before_output'] . $output . $this->args['after_output'];
	    }

	    /**
	     * Filter snap_pagination output.
	     *
	     * @since  1.0.0
	     *
	     * @var string $output The output HTML for the pagination.
	     * @return  string The filtered HTML.
	     */
	    $output = apply_filters('snap_pagination_output', $output);

	    return $output;
    }

    /**
     * Return Pagination default arguments.
     *
     * @since 1.0.0
     * 
     * @return array Default arguments.
     */
    private function get_defaults()
    {
    	return [
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
    }

    /**
     * Sets the page_count variable from $this->wp_query.
     *
     * @since  1.0.0
     */
    private function set_page_count()
    {
    	if ($this->wp_query instanceof WP_User_Query) {
	        $this->page_count = (int) empty($this->wp_query->get_results()) ? 0 : ceil($this->wp_query->get_total() / $this->wp_query->query_vars['number']);
	    } else {
	        $this->page_count = (int) $this->wp_query->max_num_pages;
	    }
    }

    /**
     * Sets the current_page variable.
     *
     * @since  1.0.0
     */
    private function set_current_page()
    {
    	$this->current_page = empty(get_query_var('paged')) ? 1 : intval(get_query_var('paged'));
    }

    /**
     * Sets the ranges used when calculating how many links to display.
     *
     * @since  1.0.0
     */
    private function set_ranges()
    {
    	$this->args['range'] = (int) $this->args['range'] - 1;
    	$this->args['ceil'] = absint(ceil($this->args['range'] / 2));
    }

    /**
     * Returns the min and max ranges used when calculating the page links.
     *
     * @since  1.0.0
     * 
     * @return array [$min, $max]
     */
    private function get_min_max()
    {
    	if ($this->page_count > $this->args['range']) {
	        if ($this->current_page <= $this->args['range']) {
	            return [
	            	1, 
	            	$this->args['range'] + 1
	            ];
	        } elseif ($this->current_page >= ($this->page_count - $this->args['ceil'])) {
	            return [
	            	$this->page_count - $this->args['range'], 
	            	$this->page_count
	            ];
	        } elseif ($this->current_page >= $this->args['range'] && $this->current_page < ($this->page_count - $this->args['ceil'])) {
	             return [
	            	$this->current_page - $this->args['ceil'],
					$this->current_page + $this->args['ceil']
	            ];
	        }
	    } else {
	        return [1, $this->page_count];
	    }
    }

    /**
     * Create HTML for the first/previous page links.
     *
     * @since  1.0.0
     * 
     * @return sting
     */
    private function get_opening_links()
    {
    	$output = '';

    	$previous_link = esc_attr(get_pagenum_link(intval($this->current_page) - 1));
	    $first_page_link = esc_attr(get_pagenum_link(1));

    	if ($this->args['show_first_last'] && $first_page_link && $this->current_page > 2) {
	        $output .= sprintf($this->args['first_wrapper'], $first_page_link);
	    }

	    if ($this->args['show_previous_next'] && $previous_link && $this->current_page !== 1) {
	        $output .= sprintf($this->args['previous_wrapper'], $previous_link);
	    }

	    return $output;
    }    

    /**
     * Create HTML for the pagination links.
     *
     * @since  1.0.0
     * 
     * @return sting
     */
    private function get_links()
    {
    	list($min, $max) = $this->get_min_max();
    	$output = '';

	    if (! empty($min) && ! empty($max) && $this->args['range'] >= 0) {
	        for ($i = $min; $i <= $max; $i++) {
	            if ($this->current_page == $i) {
	                // output active HTML
	                $output .= sprintf($this->args['active_link_wrapper'], $i);
	            } else {
	                // output link HTML
	                $output .= sprintf(
	                    $this->args['link_wrapper'],
	                    esc_attr(get_pagenum_link($i)),
	                    number_format_i18n($i)
	                );
	            }
	        }
	    }

	    return $output;
    }

    /**
     * Create HTML for the next/last page links.
     *
     * @since  1.0.0
     * 
     * @return sting
     */
    private function get_closing_links()
    {
    	$output = '';

	    $next_link = esc_attr(get_pagenum_link(intval($this->current_page) + 1));
	    $last_page_link = esc_attr(get_pagenum_link($this->page_count));

		if ($this->args['show_previous_next'] && $next_link && $this->page_count != $this->current_page) {
	        $output .= sprintf($this->args['next_wrapper'], $next_link);
	    }

	    if ($this->args['show_first_last'] && $last_page_link) {
	        $output .= sprintf($this->args['last_wrapper'], $last_page_link);
	    }

	    return $output;
    }
}