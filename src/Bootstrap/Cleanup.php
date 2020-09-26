<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;
use Snap\Services\Config;

/**
 * Cleanup WordPress output and functionality.
 */
class Cleanup extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'widgets_init' => 'removePointlessWidgets',
        'init' => 'cleanWpHead',
        'admin_bar_init' => 'moveAdminBarInlineStyles',
        'admin_menu' => [
            999 => 'removeEditorLinks',
        ],
        'load-theme-editor.php' => 'restrictAccess',
        'load-plugin-editor.php' => 'restrictAccess',
        'admin_bar_menu' => [
            99 => 'cleanAdminBar',
        ],
    ];

    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'style_loader_tag' => 'cleanAssetTags',
        'body_class' => 'cleanupBodyClasses'
    ];

    /**
     * Conditionally add filters.
     */
    public function boot(): void
    {
        // xmlrpc is a potential security weakness. Most of the time it is completely irrelevant.
        if (Config::get('disable_xmlrpc')) {
            $this->addFilter('xmlrpc_enabled', '__return_false');
        }
    }

    /**
     * Move all frontend admin bar css and js to footer.
     */
    public function moveAdminBarInlineStyles(): void
    {
        if (!\is_admin()) {
            // Remove the inline styles normally added by the admin bar and move to the footer.
            $this->removeAction('wp_head', '_admin_bar_bump_cb');
            $this->removeAction('wp_head', 'wp_admin_bar_header');
            $this->addAction('wp_footer', 'wp_admin_bar_header');
            $this->addAction('wp_footer', '_admin_bar_bump_cb');

            // Unregister the main admin bar css files...
            \wp_dequeue_style('admin-bar');

            // ... and print to footer.
            $this->addAction(
                'wp_footer',
                function () {
                    \wp_enqueue_style('admin-bar');
                }
            );
        }
    }

    /**
     * Remove some useless default widgets.
     */
    public function removePointlessWidgets(): void
    {
        // Just why?
        \unregister_widget('WP_Widget_Meta');

        // There are better ways of doing this.
        \unregister_widget('WP_Widget_RSS');
    }

    /**
     * Removes clutter from the admin bar.
     *
     * @param  \WP_Admin_Bar $wp_admin_bar Global WP_Admin_Bar instance.
     */
    public function cleanAdminBar(\WP_Admin_Bar $wp_admin_bar): void
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    /**
     * Remove theme and plugin editor links from admin.
     *
     * We do not disable via DISALLOW_FILE_EDIT as some plugins need this.
     * Feel free to override this in your wp-config.
     */
    public function removeEditorLinks(): void
    {
        \remove_submenu_page('themes.php', 'theme-editor.php');
        \remove_submenu_page('plugins.php', 'plugin-editor.php');
    }

    /**
     * Remove un needed attributes from asset tags.
     *
     * @param  string $tag Original asset tag.
     * @return string
     */
    public function cleanAssetTags(string $tag): string
    {
        \preg_match_all(
            "!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!",
            $tag,
            $matches
        );

        if (empty($matches[2])) {
            return $tag;
        }

        $media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';

        return "\t" . '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
    }

    /**
     * Sends 403 header and related message to the browser.
     *
     */
    public function restrictAccess(): void
    {
        \wp_die('You are not allowed to be here', 403);
    }

    /**
     * Clean up wp_head().
     */
    public function cleanWpHead(): void
    {
        global $wp_widget_factory;

        $this->removeAction('wp_head', 'feed_links_extra', 3);

        // Remove emojis.
        $this->removeEmojis();

        // Remove next/previous links.
        $this->removeAction('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

        // Remove oembed.
        $this->removeAction('wp_head', 'wp_oembed_add_discovery_links');
        $this->removeAction('wp_head', 'wp_oembed_add_host_js');

        // Generic fixes.
        $this->removeAction('wp_head', 'rsd_link');
        $this->removeAction('wp_head', 'wlwmanifest_link');
        $this->removeAction('wp_head', 'wp_generator');
        $this->removeAction('wp_head', 'wp_shortlink_wp_head', 10);
        $this->removeAction('wp_head', 'rest_output_link_wp_head', 10);

        if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
            $this->removeAction(
                'wp_head',
                [
                    $wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
                    'recent_comments_style',
                ]
            );
        }

        $this->addFilter('use_default_gallery_style', '__return_false');
    }

    /**
     * Remove extra classes from body_class and tidy up output.
     *
     * @param array $classes
     * @return array
     */
    public function cleanupBodyClasses(array $classes): array
    {
        if (\is_page_template()) {
            $classes = \array_filter($classes, static function (string $class) {
                return !\strpos($class, '-template') !== false;
            });

            // Add the sanitized class
            $template = \explode('/', \get_page_template_slug());

            $classes[] = \get_post_type() . '-template-' .  \str_replace(['.php', '.blade'], '', \end($template));
        }

        return $classes;
    }

    /**
     * Remove emoji js and css site-wide.
     */
    private function removeEmojis(): void
    {
        $this->removeHook(['the_content_feed', 'comment_text_rss'], 'wp_staticize_emoji');
        $this->removeHook('wp_head', 'print_emoji_detection_script', 7);
        $this->removeHook('wp_mail', 'wp_staticize_emoji_for_email');
        $this->removeHook('admin_print_scripts', 'print_emoji_detection_script');
        $this->removeHook(['admin_print_styles', 'wp_print_styles'], 'print_emoji_styles');
        $this->addFilter('emoji_svg_url', '__return_false');
    }
}
