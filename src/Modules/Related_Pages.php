<?php

namespace Snap\Core\Modules;

use Snap\Core\Utils;

/**
 * 
 *
 * @since  1.0.0
 */
class Related_Pages
{
	/**
	 * Related_Pages arguments array.
	 *
	 * @since  1.0.0
	 * @var array
	 */
    private $args = [];

    /**
     * Create $args array and assign object properties.
     *
     * @since  1.0.0
     * 
     * @param array $args {
	 *     Optional. An array of arguments.
	 *
	 *     @type bool   $echo  		     	 
	 *     @type int    $range 		     	 
	 *     @type object $custom_query    	 
	 *     @type bool   $show_first_last     
	 *     @type bool   $show_previous_next  
	 *     @type string $before_output		 
	 *     @type string $after_output		 
	 *     @type string $active_link_wrapper 
	 *     @type string $link_wrapper 		 
	 *     @type string $next_wrapper	 	 
	 *     @type string $previous_wrapper	 
	 *     @type string $first_wrapper 		 
	 *     @type string $last_wrapper	 	 
	 *           						     
	 * }
     */
    public function __construct($args = [])
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
	        apply_filters('snap_related_pages_defaults', $this->get_defaults())
	    );
    }

    public function render()
    {
        global $post;
        
        $parent_page = Utils::get_top_level_parent_id();

        // We are on a 404 or search route
        if ($parent_page === null) {
            return false;
        }


        $settings = wp_parse_args( $args, [
            'before'          => '<h3>Related Pages</h3>',
            'after'           => '',
            'container_start' => '<ul class="f-nav f-nav-side">',
            'container_end'   => '</ul>',
            'parent_suffix'   => ' Home',
            'li_class'        => '',
            'before_link'     => '',
            'after_link'      => '',
            'before_text'     => '',
            'after_text'      => '',
            'show_parent'     => true,
            'child_of'        => $parent_page
        ] );

        // check if there are child pages
        $children = get_pages( $settings );

        if ( count($children) > 0 )
        {
            $str = $settings['before'];
            $str .= $settings['container_start'];

            if ( $settings['show_parent'] && $settings['child_of'] !== false ) 
            {
                $class = ( ! empty($post) && $settings['child_of'] == $post->ID) ? 'class="f-active '.$settings['li_class'].'"' : 'class="'.$settings['li_class'].'"';

                $str .= sprintf( 
                    '<li %s>%s<a href="%s">%s%s%s%s</a>%s</li>',
                    $class,
                    $settings['before_link'],
                    get_the_permalink($settings['child_of']),
                    $settings['before_text'],
                    get_the_title($settings['child_of']),
                    $settings['parent_suffix'],
                    $settings['after_text'],
                    $settings['after_link']
                );
            }

            foreach ( $children as $child ) 
            {
                $class = ( ! empty($post) && $child->ID == $post->ID ) ? 'class="f-active '.$settings['li_class'].'"' : 'class="'.$settings['li_class'].'"';
                
                $str .= sprintf( 
                    '<li %s>%s<a href="%s">%s%s%s</a>%s</li>',
                    $class,
                    $settings['before_link'],
                    get_the_permalink($child),
                    $settings['before_text'],
                    get_the_title($child),
                    $settings['after_text'],
                    $settings['after_link']
                );
            }
            $str .= $settings['container_end'] . $settings['after'];
            echo $str;
        } 

        return count($children) > 0;
    }

    private function get_defaults()
    {
        return [
            'before'          => '<h3>Related Pages</h3>',
            'after'           => '',
            'container_start' => '<ul class="f-nav f-nav-side">',
            'container_end'   => '</ul>',
            'parent_suffix'   => ' Home',
            'li_class'        => '',
            'before_link'     => '',
            'after_link'      => '',
            'before_text'     => '',
            'after_text'      => '',
            'show_parent'     => true,
            'child_of'        => $parent_page
        ];
    }
}