<?php

namespace Snap\Admin\Columns;

use Snap\Core\Hookable;

/**
 * Add post template column to post types.
 */
class PostTemplate extends Hookable
{
    /**
     * No need to run this Hookable when on a public request.
     *
     * @var boolean
     */
    protected $public = false;

    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [

        // Add custom columns.
        'manage_pages_columns' => 'registerColumns',
        'manage_posts_columns' => 'registerColumns',
        'manage_edit-page_sortable_columns' => 'registerSortableColumns',
        'manage_edit-post_sortable_columns' => 'registerSortableColumns',
        'request' => 'templateColumnOrderby',
    ];

    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'manage_pages_custom_column' => 'populateColumns',
        'manage_posts_custom_column' => 'populateColumns',
    ];

    /**
     * Add new columns to admin views.
     *
     * @param  array $columns WP_List_Table columns array.
     * @return array $columns
     */
    public function registerColumns($columns = [])
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
    public function populateColumns($column, $post_id)
    {
        $page_templates = \wp_get_theme()->get_page_templates(\get_post($post_id));

        switch ($column) {
            case 'snap_template':
                $template = \get_page_template_slug($post_id);
                echo isset($page_templates[$template]) ? $page_templates[$template] : '—';
                break;
        }
    }

    /**
     * Register which custom columns are sortable.
     *
     * @param  array $columns Current WP_List_Table columns.
     * @return array
     */
    public function registerSortableColumns($columns)
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
    public function templateColumnOrderby($vars)
    {
        if (\is_admin() && isset($vars['orderby']) && 'snap_template' === $vars['orderby']) {
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
