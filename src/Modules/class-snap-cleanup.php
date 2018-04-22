<?php

namespace Snap\Modules;

use Snap\Hookable;
use Snap\Loader;

class Cleanup extends Hookable {
	/**
	 * Actions to add on init.
	 * 
	 * @var array
	 */
	protected $actions = [
		'init' => 'clean_wp_head',
		'admin_bar_init' => 'move_adminbar_inline_styles',
	];

	public function boot() {
		// xmlrpc is a potential security weakness. Most of the time it is completely irrelevent
		if ( Loader::get_option( 'disable_xmlrpc' ) ) {
			$this->add_filter( 'xmlrpc_enabled', '__return_false' );
		}
	}
	
	/**
	 * Move all admin bar css and js to footer
	 */
	public function move_adminbar_inline_styles() {
		if ( ! is_admin() ) {
			// remove the inline styles normally added by the admin bar and move to the footer
			remove_action('wp_head', '_admin_bar_bump_cb');
			remove_action( 'wp_head', 'wp_admin_bar_header' );
			add_action( 'wp_footer', 'wp_admin_bar_header' );
			add_action( 'wp_footer', '_admin_bar_bump_cb' );

			// unregister the main admin bar css files
			wp_dequeue_style( 'admin-bar' );

			// and print to footer
			add_action( 'wp_footer', function() {
				wp_enqueue_style( 'admin-bar' );
			} );
		}
	}

	/**
	 * Clean up wp_head()
	 *
	 * Remove unnecessary <link>'s
	 * Remove inline CSS and JS from WP emoji support
	 * Remove inline CSS used by Recent Comments widget
	 * Remove inline CSS used by posts with galleries
	 *
	*/
	function clean_wp_head() 
	{
		global $wp_widget_factory;

		// Originally from http://wpengineer.com/1438/wordpress-header/
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		
		// emojis
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		// remove next/previous links
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
		
		// oembed
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );

		// generic
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		
		if ( isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
			remove_action( 'wp_head', [$wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'] );
		}
		
		add_action( 'wp_head', 'ob_start', 1, 0 );
		
		add_action( 'wp_head', function () {
			$pattern = '/.*' . preg_quote( esc_url( get_feed_link( 'comments_' . get_default_feed() ) ), '/' ) . '.*[\r\n]+/';
			echo preg_replace( $pattern, '', ob_get_clean() );
		}, 3, 0);

		add_filter( 'use_default_gallery_style', '__return_false' );
	}
}


