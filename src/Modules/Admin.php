<?php

namespace Snap\Core\Modules;

use Snap\Core\Hookable;
use Snap\Core\Snap;

/**
 * Additional code directly affecting admin area.
 *
 * @since  1.0.0
 */
class Admin extends Hookable
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
        // Add additional mime types to the media filter dropdown.
        'post_mime_types' => 'additional_mime_types',
        
        // Add snap notice text to admin screens.
        'admin_footer_text' => 'branding_admin_footer',

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
        // Flush rewrite rules after theme activation - always a good idea!
        'after_switch_theme' => 'flush_rewrite_rules',

        // Output custom column content.
        'manage_pages_custom_column' => 'populate_columns',
        'manage_posts_custom_column' => 'populate_columns',
    ];
    
    /**
     * Add some additional mime type filters to media pages.
     *
     * @since  1.0.0
     *
     * @param  array $post_mime_types The current list of mime types.
     * @return array The original list with our additional types.
     */
    public function additional_mime_types($post_mime_types)
    {
        $additional_mime_types = [
            'application/msword' => [
                \__('Word Docs', 'snap'),
                \__('Manage Word Docs', 'snap'),
                \_n_noop('Word Doc <span class="count">(%s)</span>', 'Word Docs <span class="count">(%s)</span>'),
            ],
            'application/vnd.ms-excel' => [
                \__('Excel Docs', 'snap'),
                \__('Manage Excel Docs', 'snap'),
                \_n_noop('Excel Doc <span class="count">(%s)</span>', 'Excel Docs <span class="count">(%s)</span>'),
            ],
            'application/pdf' => [
                \__('PDFs', 'snap'),
                \__('Manage PDFs', 'snap'),
                \_n_noop('PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>'),
            ],
            'application/zip' => [
                \__('ZIPs', 'snap'),
                \__('Manage ZIPs', 'snap'),
                \_n_noop('ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>'),
            ],
            'text/csv' => [
                \__('CSVs', 'snap'),
                \__('Manage CSVs', 'snap'),
                \_n_noop('CSV <span class="count">(%s)</span>', 'CSVs <span class="count">(%s)</span>'),
            ],
        ];

        return \array_merge($post_mime_types, $additional_mime_types);
    }

    /**
     * Outputs the SnapWP footer in WordPress admin.
     *
     * @since  1.0.0
     */
    public function branding_admin_footer()
    {
        echo \sprintf(
            '%s <a href="http://wordpress.org" target="_blank">WordPress</a> %s <a href="%s" target="_blank">SnapWP</a>',
            \__('Built using', 'snap'),
            \__('and', 'snap'),
            \esc_url(Snap::SNAPWP_HOME)
        );
    }

    /**
     * Flush rewrite rules for custom post types after theme is switched.
     *
     * @since  1.0.0
     */
    public function flush_rewrite_rules()
    {
        \flush_rewrite_rules();
    }

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
        $page_templates = \wp_get_theme()->get_page_templates($post_id);

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
