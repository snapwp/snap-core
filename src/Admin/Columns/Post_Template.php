<?php

namespace Snap\Admin\Columns;

use Snap\Core\Hookable;

/**
 * Add post template column to post types.
 *
 * @since 1.0.0
 */
class Post_template extends Hookable
{
    /**
     * No need to run this Hookable when on a public request.
     *
     * @since  1.0.0
     * @var boolean
     */
    protected $public = false;

    /**
     * Filters to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [

        // Add custom columns.
        'manage_pages_columns' => 'register_columns',
        'manage_posts_columns' => 'register_columns',
        'manage_edit-page_sortable_columns' => 'register_sortable_columns',
        'manage_edit-post_sortable_columns' => 'register_sortable_columns',
        'request' => 'template_column_orderby',
    ];

    /**
     * Actions to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $actions = [
        'manage_pages_custom_column' => 'populate_columns',
        'manage_posts_custom_column' => 'populate_columns',
    ];

    /**
     * Add new columns to admin views.
     *
     * @since  1.0.0
     *
     * @param  array $columns WP_List_Table columns array.
     * @return array $columns
     */
    public function register_columns($columns = [])
    {
        return \array_merge(
            $columns,
            [
                'snap_template' => 'Template',
            ]
        );
    }

    /**
     * Populate custom columns.
     *
     * @since  1.0.0
     *
     * @param string $column Column name.
     * @param int    $post_id The post ID.
     */
    public function populate_columns($column, $post_id)
    {
        $page_templates = \wp_get_theme()->get_page_templates(\get_post($post_id));

        switch ($column) {
            case 'snap_template':
                $template = \get_page_template_slug($post_id);
                echo isset($page_templates[ $template ]) ? $page_templates[ $template ] : '—';
                break;
        }
    }

    /**
     * Register which custom columns are sortable.
     *
     * @since  1.0.0
     *
     * @param  array $columns Current WP_List_Table columns.
     * @return array
     */
    public function register_sortable_columns($columns)
    {
        $columns['snap_template'] = 'snap_template';
        return $columns;
    }

    /**
     * Define custom orderby rules for custom sortable columns.
     *
     * @since  1.0.0
     *
     * @param  array $vars Request query vars before being passed to the global WP_Query, and into pre_get_posts.
     * @return array
     */
    public function template_column_orderby($vars)
    {
        if (is_admin() && isset($vars['orderby']) && 'snap_template' === $vars['orderby']) {
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
