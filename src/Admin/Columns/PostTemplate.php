<?php

namespace Snap\Admin\Columns;

use Snap\Core\Hookable;

/**
 * Add post template column to post types.
 */
class PostTemplate extends Hookable
{
    /**
     * @var boolean
     */
    protected $public = false;

    /**
     * Register hooks.
     */
    public function boot()
    {
        $this->addFilter(['manage_pages_columns', 'manage_posts_columns'], 'registerColumns');
        $this->addFilter(['manage_edit-page_sortable_columns', 'manage_edit-post_sortable_columns'], 'registerSortableColumns');
        $this->addFilter('request', 'templateColumnOrderby');

        $this->addAction(['manage_pages_custom_column', 'manage_posts_custom_column'], 'populateColumns');
    }

    /**
     * Add new columns to admin views.
     *
     * @param  array $columns WP_List_Table columns array.
     * @return array
     */
    public function registerColumns(array $columns = []): array
    {
        if (!empty(\wp_get_theme()->get_page_templates(null, \get_query_var('post_type')))) {
            return \array_merge(
                $columns,
                [
                    'snap_template' => 'Template',
                ]
            );
        }

        return $columns;
    }

    /**
     * Populate custom columns.
     *
     * @param string $column  Column name.
     * @param int    $post_id The post ID.
     */
    public function populateColumns(string $column, int $post_id): void
    {
        $page_templates = \wp_get_theme()->get_page_templates(\get_post($post_id));

        if ($column === 'snap_template') {
            $template = \get_page_template_slug($post_id);
            echo $page_templates[$template] ?? '—';
        }
    }

    /**
     * Register which custom columns are sortable.
     *
     * @param  array $columns Current WP_List_Table columns.
     * @return array
     */
    public function registerSortableColumns($columns): array
    {
        $columns['snap_template'] = 'snap_template';
        return $columns;
    }

    /**
     * Define custom orderby rules for custom sortable columns.
     *
     * @param  array $vars Request query vars before being passed to the global WP_Query, and into pre_get_posts.
     * @return array
     */
    public function templateColumnOrderby(array $vars): array
    {
        if ((isset($vars['orderby']) && 'snap_template' === $vars['orderby']) && \is_admin()) {
            $vars = \array_merge(
                $vars,
                [
                    'orderby' => 'not_exists_clause title',
                    'meta_query' => [
                        'relation' => 'OR',
                        'exists_clause' => [
                            'key' => '_wp_page_template',
                            'compare' => 'EXISTS',
                        ],
                        'not_exists_clause' => [
                            'key' => '_wp_page_template',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ]
            );
        }

        return $vars;
    }
}
