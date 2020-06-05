<?php

namespace Snap\Templating;

use Snap\Core\Hookable;
use Snap\Services\View;
use Snap\Utils\Theme;

/**
 * Ensure all post templates found in resources/templates/ folder get treated as templates by WordPress,
 * and ensure all is_page_template() requests are routed to routes.php.
 */
class HandlePostTemplates extends Hookable
{
    public function boot(): void
    {
        $this->addFilter('after_setup_theme', 'addThemePostTemplatesToCache');
        // Helpful to caches such as Redis, which may purge on post save
        $this->addFilter('edit_post', 'addThemePostTemplatesToCache', 99);

        $this->addFilter('template_include', 'postTemplateRouting');
        $this->addFilter('get_search_form', 'getSearchForm');
    }

    /**
     * Ensure get_search_form still works and is mapped to modules/searchform.
     *
     * @return string Markup for partials/searchform.php.
     */
    public function getSearchForm(): string
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
        return \ob_get_clean();
    }

    /**
     * Scans the templates folder and adds any templates found to the global template array.
     *
     * @return array
     */
    public function customTemplateLocator(): array
    {
        $post_templates = [];

        // Path to  templates folder.
        $path = \get_stylesheet_directory() . '/' . Theme::getTemplatesPath() . 'views/post-templates/';

        if (!\is_dir($path)) {
            return [];
        }

        $templates = \scandir($path);

        if (!empty($templates)) {
            foreach ($templates as $tpl) {
                $full_path = $path . $tpl;

                if ($tpl === '.' || $tpl === '..' || \is_dir($full_path) || \strpos($tpl, '_example') !== false) {
                    continue;
                }

                if (!\preg_match('|Template Name:(.*)$|mi', \file_get_contents($full_path), $header)) {
                    continue;
                }

                $types = ['page'];

                if (\preg_match('|Template Post Type:(.*)$|mi', \file_get_contents($full_path), $type)) {
                    $types = \explode(
                        ',',
                        \_cleanup_header_comment(
                            \str_replace(' ', '', $type[1])
                        )
                    );
                }

                foreach ($types as $type) {
                    $type = sanitize_key($type);

                    if (!isset($post_templates[$type])) {
                        $post_templates[$type] = [];
                    }

                    $key = Theme::getPostTemplatesPath($tpl);
                    $post_templates[$type][$key] = _cleanup_header_comment($header[1]);
                }
            }
        }

        return $post_templates;
    }

    /**
     * Register the page-template loader for all available public post types
     */
    public function addThemePostTemplatesToCache(): void
    {
        $cache_key = 'post_templates-' . \md5(\get_theme_root() . '/' . \get_stylesheet());
        $templates = \wp_get_theme()->get_post_templates();
        \wp_cache_replace($cache_key, \array_merge($templates, $this->customTemplateLocator()), 'themes', 1800);
    }

    /**
     * Ensure post template requests get routed to our main front controller instead of some random file.
     *
     * @param string $template_path Path of template to load.
     * @return string Path of template to load.
     */
    public function postTemplateRouting($template_path): string
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
