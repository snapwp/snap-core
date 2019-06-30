<?php

namespace Snap\Templating;

use Snap\Core\Hookable;
use Snap\Services\Config;
use Snap\Services\View;
use Snap\Utils\Theme;

/**
 * Ensure all post templates found in resources/templates/ folder get treated as templates by WordPress,
 * and ensure all is_page_template() requests are routed to routes.php.
 */
class HandlePostTemplates extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'after_setup_theme' => 'registerThemeTemplateHooks',
        'template_include' => 'postTemplateRouting',
        'get_search_form' => 'getSearchForm',
    ];

    /**
     * Ensure get_search_form still works and is mapped to modules/searchform.
     *
     * @return string Markup for parials/searchform.php.
     */
    public function getSearchForm()
    {
        $data = [];

        if (\function_exists('random_int')) {
            try {
                $data['searchform_id'] = 'search_' . \random_int(1000, 8000);
            } catch (\Exception $e) {
                // Fail silently.
            }
        }

        if (!isset($data['searchform_id'])) {
            $data = [
                'searchform_id' => \uniqid('search_', true),
            ];
        }

        \ob_start();
        View::partial('searchform', $data);
        $form = \ob_get_clean();

        return $form;
    }

    /**
     * Scans the templates folder and adds any templates found to the global template array.
     *
     * @param array $post_templates Array of page templates. Keys are filenames,
     *                              values are translated names.
     * @param \WP_Theme $wp_theme   The theme object.
     * @param \WP_Post|null $post   The post being edited, provided for context, or null.
     * @param string $post_type     Post type to get the templates for.
     * @return array                Modified array of page templates
     */
    public function customTemplateLocator($post_templates, $wp_theme, $post, $post_type)
    {
        // Path to  templates folder.
        $path = \get_stylesheet_directory() . '/' . Theme::getTemplatesPath() . 'views/post-templates/';

        $templates = \scandir($path);

        if (!empty($templates)) {
            foreach ($templates as $tpl) {
                $full_path = $path . $tpl;

                if ($tpl === '.' || $tpl === '..' || \is_dir($full_path) || $tpl === '_example.php') {
                    continue;
                }

                if (!\preg_match('|Template Name:(.*)$|mi', \file_get_contents($full_path), $header)) {
                    continue;
                }

                $types = ['page'];

                if (\preg_match('|Template Post Type:(.*)$|mi', \file_get_contents($full_path), $type)) {
                    $types = \explode(',', \_cleanup_header_comment(\str_replace(' ', '', $type[1])));
                }

                if (\in_array($post_type, $types)) {
                    $post_templates[Theme::getTemplatesPath() . 'views/post-templates/' . $tpl] = \trim($header[1]);
                }
            }
        }

        return $post_templates;
    }

    /**
     * Register the page-template loader for all available public post types
     */
    public function registerThemeTemplateHooks()
    {
        foreach (\get_post_types(['public' => true]) as $post_type) {
            $this->addFilter("theme_{$post_type}_templates", 'customTemplateLocator');
        }
    }

    /**
     * Ensure post template requests get routed to our main front controller instead of some random file.
     *
     * @param  string $template_path Path of template to load.
     * @return string Path of template to load.
     */
    public function postTemplateRouting($template_path)
    {
        $routes_file = \get_template_directory() . '/public/routes.php';

        if (\is_child_theme()) {
            $routes_file = \locate_template('public/routes.php');
        }

        if ('' !== $routes_file) {
            return $routes_file;
        }

        return $template_path;
    }
}
