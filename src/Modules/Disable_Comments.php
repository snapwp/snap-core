<?php

namespace Snap\Core\Modules;

use Snap\Core\Hookable;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 *
 * @since  1.0.0
 */
class Disable_Comments extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'comments_array' => [
            20 => '__return_empty_array',
        ],
        'comments_open' => [
            20 => '__return_false'
        ],
        'pre_option_default_pingback_flag' => '__return_zero'
    ];

    /**
     * Actions to add on init.
     * @since  1.0.0
     *
     * @var array
     */
    protected $actions = [
        'widgets_init' => 'remove_comments_widget',
        'admin_print_footer_scripts-index.php' => 'remove_comments_dashboard_css',
        'wp_dashboard_setup' => 'remove_comments_dashboard_widget',
        'admin_menu' => 'remove_comments_access',
        'template_redirect' => [
            9 => 'remove_comments_stop_bots'
        ],
        'add_meta_boxes' => [
            9999 => 'remove_comments_meta_boxes'
        ]
    ];

    /**
     * Boot up the class.
     *
     * Add hooks to disable comments.
     *
     * @since 1.0.0
     */
    public function boot()
    {
        // Remove admin bar references to comments.
        $this->add_action([ 'admin_init', 'template_redirect' ], 'remove_comments_from_adminbar');

        // Ensures all new posts are set to comments closed by default.
        $this->add_action([ 'edit_form_advanced', 'edit_page_form' ], 'remove_comments_set_closed_status');
    }

    /**
     * Stop admin access to comments pages.
     *
     * @since 1.0.0
     */
    public function remove_comments_access()
    {
        global $pagenow;

        if ($pagenow == 'comment.php' || $pagenow == 'edit-comments.php' || $pagenow == 'options-discussion.php') {
            wp_die(__('Comments are closed.'), '', [ 'response' => 403 ]);
        }

        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Adds CSS to the dashboard screen to remove references to comments.
     *
     * @since 1.0.0
     */
    public function remove_comments_dashboard_css()
    {
        echo '<style>#dashboard_right_now .comment-count, #latest-comments, #welcome-panel .welcome-comments {display: none;}</style>';
    }

    /**
     * Removes comment dashboard widget from admin.
     *
     * @since 1.0.0
     */
    public function remove_comments_dashboard_widget()
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * Removes comments dropdown from the admin bar.
     *
     * @since 1.0.0
     */
    public function remove_comments_from_adminbar()
    {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }
        
    /**
     * Remove comment meta boxes from all post types.
     *
     * @since  1.0.0
     */
    public function remove_comments_meta_boxes()
    {
        if (is_admin() && current_user_can('manage_options')) {
            // Get all public post types.
            $post_types = get_post_types([ 'public' => true ]);

            foreach ($post_types as $post_type) {
                remove_meta_box('commentstatusdiv', $post_type, 'normal');
                remove_meta_box('commentsdiv', $post_type, 'normal');
                remove_meta_box('trackbacksdiv', $post_type, 'normal');
            }
        }
    }

    /**
     * Ensures all new posts are set to comments closed.
     *
     * @since 1.0.0
     */
    public function remove_comments_set_closed_status()
    {
        echo '<input type="hidden" name="comment_status" value="closed" /><input type="hidden" name="ping_status" value="closed" />';
    }
    
    /**
     * Returns a 403 if someone (bots) ends up on a front end comments URL.
     *
     * @since 1.0.0
     */
    public function remove_comments_stop_bots()
    {
        if (is_comment_feed()) {
            wp_die(__('Comments are closed.'), '', [ 'response' => 403 ]);
        }
    }

    /**
     * Removes the comments widget.
     *
     * @since 1.0.0
     */
    public function remove_comments_widget()
    {
        unregister_widget('WP_Widget_Recent_Comments');
    }
}
