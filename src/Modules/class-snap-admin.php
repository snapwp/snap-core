<?php

namespace Snap\Modules;

use Snap\Hookable;
use Snap\Loader;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 *
 * @since  1.0.0
 */
class Admin extends Hookable {
	/**
	 * Filters to add on init.
	 * 
	 * @var array
	 */
	protected $filters = [
		// Add additional mime types to the media filter dropdown.
		'post_mime_types' => 'additional_mime_types',
		
		// Add snap notice text to admin screens.
		'admin_footer_text' => 'branding_admin_footer',
	];

	/**
	 * Actions to add on init.
	 * 
	 * @var array
	 */
	protected $actions = [
		// Flush rewrite rules after theme activation - always a good idea!
		'after_switch_theme' => 'flush_rewrite_rules',
	];

	/**
	 * Boot up the class.
	 *
	 * Add hooks to disable comments.
	 *
	 * @since 1.0.0
	 */
	public function boot() {
    	// Completely disable WordPress comments.
    	if ( Loader::get_option( 'disable_comments' ) ) {
			// Ensures all code which uses comments gets fed an empty array.
			$this->add_filter( 'comments_array',  '__return_empty_array' , 20 );

			// Ensure comments_open() always retuns false.
			$this->add_filter( 'comments_open',  '__return_false' , 20 );

			// Needed to stop stupid pingbacks.
			$this->add_filter( 'pre_option_default_pingback_flag', '__return_zero' );

    		// Remove admin bar references to comments.
    		$this->add_action( [ 'admin_init', 'template_redirect' ], 'remove_comments_from_adminbar' );

    		// Stop admin access to comment admin pages.
    		$this->add_action('admin_menu', 'remove_comments_access' );

    		// Stop frontend access to comment feeds.
    		$this->add_action( 'template_redirect', 'remove_comments_stop_bots', 9 );

			// Remove the comments dashboard widget.
			$this->add_action( 'wp_dashboard_setup','remove_comments_dashboard_widget' );
			
			// Remove the comments meta boxes.
			$this->add_action( 'add_meta_boxes','remove_comments_meta_boxes', 9999 );

			// Ensures all new posts are set to comments closed by default.
			$this->add_action( [ 'edit_form_advanced', 'edit_page_form' ], 'remove_comments_set_closed_status' );

			// Tidy up dashboard widgets.
			$this->add_action( 'admin_print_footer_scripts-index.php', 'remove_comments_dashboard_css' );

			// Remove comments widget.
			$this->add_action( 'widgets_init', 'remove_comments_widget' );
    	}
	}


	
	/**
	 * Add some additional mime type filters to media pages.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $post_mime_types The current list of mime types
	 * @return array                  The original list with our additional types
	 */
	public function additional_mime_types( $post_mime_types ) {
		$additional_mime_types = [
			'application/msword' => [ 
				__( 'Word Docs', 'snap' ),
				__( 'Manage Word Docs', 'snap' ), 
				_n_noop('Word Doc <span class="count">(%s)</span>', 'Word Docs <span class="count">(%s)</span>') 
			],
			'application/vnd.ms-excel' => [ 
				__( 'Excel Docs', 'snap' ), 
				__( 'Manage Excel Docs', 'snap' ), 
				_n_noop('Excel Doc <span class="count">(%s)</span>', 'Excel Docs <span class="count">(%s)</span>') 
			],
			'application/pdf' => [ 
				__( 'PDFs', 'snap' ), 
				__( 'Manage PDFs', 'snap' ), 
				_n_noop('PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>') 
			],
			'application/zip' => [ 
				__( 'ZIPs', 'snap' ), 
				__( 'Manage ZIPs', 'snap' ), 
				_n_noop('ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>') 
			],
			'text/csv' => [ 
				__( 'CSVs', 'snap' ), 
				__( 'Manage CSVs', 'snap' ), 
				_n_noop('CSV <span class="count">(%s)</span>', 'CSVs <span class="count">(%s)</span>') 
			],
		];

        return array_merge( $post_mime_types, $additional_mime_types );
    }

	/**
	 * Outputs the 'designed by snap media' footer in wordpress admin.
	 *
	 * @since  1.0.0
	 */
	public function branding_admin_footer() {
		echo sprintf(
			'%s <a href="http://wordpress.org" target="_blank">WordPress</a> %s <a href="" target="_blank">WP Artisan</a>',
			__( 'Built using', 'snap' ),
			__( 'and', 'snap' )
		);
	}

	/**
	 * Flush rewrite rules for custom post types after theme is switched.
	 *
	 * @since  1.0.0
	 */
	public function flush_rewrite_rules() {
	    flush_rewrite_rules();
	}

	/**
	 * Stop admin access to comments pages.
	 * 
	 * @since 1.0.0
	 */
	function remove_comments_access() {
		global $pagenow;

		if ( $pagenow == 'comment.php' || $pagenow == 'edit-comments.php' || $pagenow == 'options-discussion.php' ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}

		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );	
	}

	/**
	 * Adds CSS to the dashboard screen to remove references to comments.
	 * 
	 * @since 1.0.0
	 */
	function remove_comments_dashboard_css() {
		echo '<style>#dashboard_right_now .comment-count, #latest-comments, #welcome-panel .welcome-comments {display: none;}</style>';
	}	

	/**
	 * Removes comment dashboard widget from admin.
	 * 
	 * @since 1.0.0
	 */
	public function remove_comments_dashboard_widget() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Removes comments dropdown from the admin bar.
	 * 
	 * @since 1.0.0
	 */
	public function remove_comments_from_adminbar() {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}
		
	/**
	 * Remove comment meta boxes from all post types.
	 * 
	 * @since  1.0.0
	 */
	public function remove_comments_meta_boxes() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			// Get all public post types.
			$post_types = get_post_types( [ 'public' => true ] ); 

    		foreach ( $post_types as $post_type ) {
				remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
				remove_meta_box( 'commentsdiv', $post_type, 'normal' );
				remove_meta_box( 'trackbacksdiv', $post_type, 'normal' );
			}
		}
	}

	/**
	 * Ensures all new posts are set to comments closed.
	 * 
	 * @since 1.0.0
	 */
	function remove_comments_set_closed_status() {
		echo '<input type="hidden" name="comment_status" value="closed" /><input type="hidden" name="ping_status" value="closed" />';	
	}
	
	/**
	 * Returns a 403 if someone (Bots) ends up on a front end comments URL.
	 * 
	 * @since 1.0.0
	 */
	function remove_comments_stop_bots() {
		if ( is_comment_feed() ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Removes the comments widget.
	 * 
	 * @since 1.0.0
	 */
	function remove_comments_widget() {
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}
}


