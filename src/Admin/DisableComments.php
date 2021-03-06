<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 */
class DisableComments extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'comments_array' => [
            20 => '__return_empty_array',
        ],
        'comments_open' => [
            20 => '__return_false',
        ],
        'pre_option_default_pingback_flag' => '__return_zero',
        'admin_init' => 'removePostTypeSupport',
    ];

    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'widgets_init' => 'removeCommentsWidget',
        'admin_print_footer_scripts-index.php' => 'removeCommentsDashboardCss',
        'wp_dashboard_setup' => 'removeCommentsDashboardWidget',
        'admin_menu' => 'removeCommentsAccess',
        'template_redirect' => [
            9 => 'removeCommentsStopBots',
        ],
        'add_meta_boxes' => [
            9999 => 'removeCommentsMetaBoxes',
        ],
    ];

    /**
     * Add hooks to disable comments.
     */
    public function boot()
    {
        // Remove admin bar references to comments.
        $this->addAction(['admin_init', 'template_redirect'], 'removeCommentsFromAdminbar');

        // Ensures all new posts are set to comments closed by default.
        $this->addAction(['edit_form_advanced', 'edit_page_form'], 'removeCommentsSetClosedStatus');
    }

    /**
     * Stop admin access to comments pages.
     */
    public function removeCommentsAccess()
    {
        global $pagenow;

        if ($pagenow === 'comment.php' || $pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
            \wp_die(\__('Comments are closed.', 'snap'), '', [ 'response' => 403 ]);
        }

        \remove_menu_page('edit-comments.php');
        \remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Adds CSS to the dashboard screen to remove references to comments.
     */
    public function removeCommentsDashboardCss()
    {
        echo '<style>',
            '#dashboard_right_now .comment-count,#latest-comments, #welcome-panel .welcome-comments {display: none;}',
            '</style>';
    }

    /**
     * Removes comment dashboard widget from admin.
     */
    public function removeCommentsDashboardWidget()
    {
        \remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * Removes comments dropdown from the admin bar.
     */
    public function removeCommentsFromAdminbar()
    {
        if (is_admin_bar_showing()) {
            \remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    /**
     * Remove comment meta boxes from all post types.
     */
    public function removeCommentsMetaBoxes()
    {
        if (\is_admin() && \current_user_can('manage_options')) {
            // Get all public post types.
            $post_types = \get_post_types([ 'public' => true ]);

            foreach ($post_types as $post_type) {
                \remove_meta_box('commentstatusdiv', $post_type, 'normal');
                \remove_meta_box('commentsdiv', $post_type, 'normal');
                \remove_meta_box('trackbacksdiv', $post_type, 'normal');
            }
        }
    }

    /**
     * Ensures all new posts are set to comments closed.
     */
    public function removeCommentsSetClosedStatus()
    {
        echo '<input type="hidden" name="comment_status" value="closed" />',
            '<input type="hidden" name="ping_status" value="closed" />';
    }

    /**
     * Returns a 403 if someone (bots) ends up on a front end comments URL.
     */
    public function removeCommentsStopBots()
    {
        if (\is_comment_feed()) {
            \wp_die(\__('Comments are closed.', 'snap'), '', [ 'response' => 403 ]);
        }
    }

    /**
     * Removes the comments widget.
     */
    public function removeCommentsWidget()
    {
        \unregister_widget('WP_Widget_Recent_Comments');
    }

    /**
     * Remove comment support from all built in post-types.
     */
    public function removePostTypeSupport()
    {
        \remove_post_type_support('page', 'comments');
        \remove_post_type_support('post', 'comments');
        \remove_post_type_support('attachment', 'comments');
    }
}
