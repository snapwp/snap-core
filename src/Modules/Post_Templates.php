<?php

namespace Snap\Core\Modules;

use Snap\Core\Snap;
use Snap\Core\Hookable;

/**
 * Ensure all post templates found in views/templates/ folder get treated as templates by WordPress,
 * and ensure all is_page_template() requests are routed to the primary front controller (index.php)
 */
class Post_Templates extends Hookable
{
    /**
     * Filters to add on init.
     * @var array
     */
    protected $filters = [
        'after_setup_theme' => 'register_theme_template_hooks',
        'template_include' => 'post_template_routing',
        'get_search_form' => 'get_search_form',
    ];

    /**
     * Ensure get_search_form still works and is mapped to modules/searchform.
     *
     * @since 1.0.0
     *
     * @param  string $old_form WP default searchform.php markup
     * @return string           Markup for modules/searchform.php
     */
    public function get_search_form($old_form)
    {
        ob_start();
        Snap::view()->partial('searchform');
        $form = ob_get_clean();

        return $form;
    }

    /**
     * Scans the custom views/templates folder and adds any templates found to the global template array
     *
     * @since  1.0.0
     *
     * @param array        $post_templates Array of page templates. Keys are filenames,
     *                                     values are translated names.
     * @param WP_Theme     $this           The theme object.
     * @param WP_Post|null $post           The post being edited, provided for context, or null.
     * @param string       $post_type      Post type to get the templates for.
     * @return array                       Modified array of page templates
     */
    public function custom_template_locator($post_templates, $wp_theme, $post, $post_type)
    {
        // path to  templates folder
        $path = get_stylesheet_directory() . '/templates/views/post-templates/';

        $templates = scandir($path);

        if (! empty($templates)) {
            foreach ($templates as $tpl) {
                $full_path = $path.$tpl;

                if ($tpl == '.' || $tpl == '..' || is_dir($full_path) || $tpl == '_example.php') {
                    continue;
                }

                if (! preg_match('|Template Name:(.*)$|mi', file_get_contents($full_path), $header)) {
                    continue;
                }

                $types = [ 'page' ];

                if (preg_match('|Template Post Type:(.*)$|mi', file_get_contents($full_path), $type)) {
                    $types = explode(',', _cleanup_header_comment(str_replace(' ', '', $type[1])));
                }

                if (in_array($post_type, $types)) {
                    $post_templates['templates/views/post-templates/' . $tpl] = trim($header[1]);
                }
            }
        }

        return $post_templates;
    }

    /**
     * Register the page-template loader for all available public post types
     *
     * @since  1.0.0
     */
    public function register_theme_template_hooks()
    {
        foreach (get_post_types([ 'public' => true ]) as $post_type) {
            add_filter("theme_{$post_type}_templates", [ $this, 'custom_template_locator' ], 10, 4);
        }
    }

    /**
     * Ensure post template requests get routed to our main front controller instead of some random file
     *
     * @since  1.0.0
     *
     * @param  string $template_path Path of template to load
     * @return string                Path of template to load
     */
    public function post_template_routing($template_path)
    {
        // if current request is a post/page template, return path to main front controller
        if (is_page_template()) {
            return get_stylesheet_directory() . '/index.php';
        }
        
        return $template_path;
    }
}
