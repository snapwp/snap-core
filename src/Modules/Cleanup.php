<?php

namespace Snap\Core\Modules;

use Snap\Core\Hookable;
use Snap\Core\Snap;

/**
 * Cleanup WordPress output and functionality.
 *
 * @since  1.0.0
 */
class Cleanup extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $actions = [
        'widgets_init' => 'remove_pointless_widgets',
        'init' => 'clean_wp_head',
        'style_loader_tag' => 'clean_asset_tags',
        'admin_bar_init' => 'move_adminbar_inline_styles',
        'admin_menu' => [
            999 => 'remove_editor_links',
        ],
        'load-theme-editor.php' => 'restrict_access',
        'load-plugin-editor.php' => 'restrict_access',
        'admin_bar_menu' => [
            99 => 'clean_adminbar',
        ],
    ];

    /**
     * Conditionally add filters.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        // xmlrpc is a potential security weakness. Most of the time it is completely irrelevant.
        if (Snap::config('disable_xmlrpc')) {
            $this->add_filter('xmlrpc_enabled', '__return_false');
        }
    }
    
    /**
     * Move all frontend admin bar css and js to footer.
     *
     * @since  1.0.0
     */
    public function move_adminbar_inline_styles()
    {
        if (! is_admin()) {
            // Remove the inline styles normally added by the admin bar and move to the footer.
            $this->remove_action('wp_head', '_admin_bar_bump_cb');
            $this->remove_action('wp_head', 'wp_admin_bar_header');
            $this->add_action('wp_footer', 'wp_admin_bar_header');
            $this->add_action('wp_footer', '_admin_bar_bump_cb');

            // Unregister the main admin bar css files...
            wp_dequeue_style('admin-bar');

            // ... and print to footer.
            $this->add_action(
                'wp_footer',
                function () {
                    wp_enqueue_style('admin-bar');
                }
            );
        }
    }

    /**
     * Remove some useless default widgets.
     *
     * @since 1.0.0
     */
    public function remove_pointless_widgets()
    {
        // Just why?
        unregister_widget('WP_Widget_Meta');

        // There are better ways of doing this.
        unregister_widget('WP_Widget_RSS');
    }

    /**
     * Removes clutter from the admin bar.
     *
     * @since  1.0.0
     *
     * @param  WP_Admin_Bar $wp_admin_bar Global WP_Admin_Bar instance.
     */
    public function clean_adminbar($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    /**
     * Remove theme and plugin editor links from admin.
     *
     * We do not disable via DISALLOW_FILE_EDIT as some plugins need this.
     * Feel free to override this in your wp-config.
     *
     * @since  1.0.0
     */
    public function remove_editor_links()
    {
        remove_submenu_page('themes.php', 'theme-editor.php');
        remove_submenu_page('plugins.php', 'plugin-editor.php');
    }

    /**
     * Remove un needed attributes from asset tags.
     *
     * @since  1.0.0
     *
     * @param  string $tag Original asset tag.
     * @return string
     */
    public function clean_asset_tags($tag)
    {
        \preg_match_all("!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $tag, $matches);
       
        if (empty($matches[2])) {
            return $tag;
        }

        $media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';

        return "\t".'<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
    }

    /**
     * Sends 403 header and related message to the browser.
     *
     * @since  1.0.0
     */
    public function restrict_access()
    {
        wp_die('You are not allowed to be here', 403);
    }

    /**
     * Clean up wp_head()
     *
     * @since  1.0.0
     */
    public function clean_wp_head()
    {
        global $wp_widget_factory;

        $this->remove_action('wp_head', 'feed_links_extra', 3);
        
        // Remove emojis.
        $this->remove_emojis();

        // Remove next/previous links.
        $this->remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        
        // Remove oembed.
        $this->remove_action('wp_head', 'wp_oembed_add_discovery_links');
        $this->remove_action('wp_head', 'wp_oembed_add_host_js');

        // Generic fixes.
        $this->remove_action('wp_head', 'rsd_link');
        $this->remove_action('wp_head', 'wlwmanifest_link');
        $this->remove_action('wp_head', 'wp_generator');
        $this->remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        $this->remove_action('wp_head', 'rest_output_link_wp_head', 10);
        
        if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
            $this->remove_action('wp_head', [$wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style']);
        }

        add_filter('use_default_gallery_style', '__return_false');
    }

    /**
     * Remove emoji js and css site-wide.
     *
     * @since 1.0.0
     */
    private function remove_emojis()
    {
        $this->remove_hook(['the_content_feed', 'comment_text_rss'], 'wp_staticize_emoji');
        $this->remove_hook('wp_head', 'print_emoji_detection_script', 7);
        $this->remove_hook('wp_mail', 'wp_staticize_emoji_for_email');
        $this->remove_hook('admin_print_scripts', 'print_emoji_detection_script');
        $this->remove_hook(['admin_print_styles', 'wp_print_styles'], 'print_emoji_styles');
        $this->add_filter('emoji_svg_url', '__return_false');
    }
}
