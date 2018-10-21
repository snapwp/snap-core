<?php

namespace Snap\Templating;

use Snap\Core\Snap;
use Snap\Core\Hookable;

/**
 * Ensure all post templates found in resources/templates/ folder get treated as templates by WordPress,
 * and ensure all is_page_template() requests are routed to routes.php.
 *
 * @since  1.0.0
 */
class Handle_Post_Templates extends Hookable
{
    /**
     * Filters to add on init.
     *
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
     * @param  string $old_form WP default searchform.php markup.
     * @return string Markup for modules/searchform.php.
     */
    public function get_search_form($old_form)
    {
        if (\function_exists('random_int')) {
            $data = [
                'searchform_id' => 'search_' . \random_int(1000, 8000),
            ];
        } else {
            $data = [
                'searchform_id' => \uniqid('search_', true),
            ];
        }

        \ob_start();
        Snap::view()->partial('searchform', $data);
        $form = \ob_get_clean();

        return $form;
    }

    /**
     * Scans the templates folder and adds any templates found to the global template array.
     *
     * @since  1.0.0
     *
     * @param array        $post_templates Array of page templates. Keys are filenames,
     *                                     values are translated names.
     * @param WP_Theme     $wp_theme       The theme object.
     * @param WP_Post|null $post           The post being edited, provided for context, or null.
     * @param string       $post_type      Post type to get the templates for.
     * @return array                       Modified array of page templates
     */
    public function custom_template_locator($post_templates, $wp_theme, $post, $post_type)
    {
        // Path to  templates folder.
        $path = \get_stylesheet_directory() . '/' . Snap::config('theme.templates_directory') . '/views/post-templates/';

        $templates = \scandir($path);

        if (! empty($templates)) {
            foreach ($templates as $tpl) {
                $full_path = $path . $tpl;

                if ($tpl === '.' || $tpl === '..' || \is_dir($full_path) || $tpl === '_example.php') {
                    continue;
                }

                if (! \preg_match('|Template Name:(.*)$|mi', \file_get_contents($full_path), $header)) {
                    continue;
                }

                $types = ['page'];

                if (\preg_match('|Template Post Type:(.*)$|mi', \file_get_contents($full_path), $type)) {
                    $types = \explode(',', \_cleanup_header_comment(\str_replace(' ', '', $type[1])));
                }

                if (\in_array($post_type, $types)) {
                    $post_templates[ Snap::config('theme.templates_directory') . '/views/post-templates/' . $tpl ] = \trim($header[1]);
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
        foreach (\get_post_types([ 'public' => true ]) as $post_type) {
            $this->add_filter("theme_{$post_type}_templates", 'custom_template_locator');
        }
    }

    /**
     * Ensure post template requests get routed to our main front controller instead of some random file.
     *
     * @since  1.0.0
     *
     * @param  string $template_path Path of template to load.
     * @return string Path of template to load.
     */
    public function post_template_routing($template_path)
    {
        $routes_file = \get_template_directory() . '/resources/routes.php';
        
        if (is_child_theme()) {
            $routes_file = \locate_template('resources/routes.php');
        }

        if ('' !== $routes_file) {
            return $routes_file;
        }
        
        return $template_path;
    }
}
