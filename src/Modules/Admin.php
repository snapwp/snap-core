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
    ];
    
    /**
     * Add some additional mime type filters to media pages.
     *
     * @since  1.0.0
     *
     * @param  array $post_mime_types The current list of mime types
     * @return array                  The original list with our additional types
     */
    public function additional_mime_types($post_mime_types)
    {
        $additional_mime_types = [
            'application/msword' => [
                __('Word Docs', 'snap'),
                __('Manage Word Docs', 'snap'),
                _n_noop('Word Doc <span class="count">(%s)</span>', 'Word Docs <span class="count">(%s)</span>')
            ],
            'application/vnd.ms-excel' => [
                __('Excel Docs', 'snap'),
                __('Manage Excel Docs', 'snap'),
                _n_noop('Excel Doc <span class="count">(%s)</span>', 'Excel Docs <span class="count">(%s)</span>')
            ],
            'application/pdf' => [
                __('PDFs', 'snap'),
                __('Manage PDFs', 'snap'),
                _n_noop('PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>')
            ],
            'application/zip' => [
                __('ZIPs', 'snap'),
                __('Manage ZIPs', 'snap'),
                _n_noop('ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>')
            ],
            'text/csv' => [
                __('CSVs', 'snap'),
                __('Manage CSVs', 'snap'),
                _n_noop('CSV <span class="count">(%s)</span>', 'CSVs <span class="count">(%s)</span>')
            ],
        ];

        return array_merge($post_mime_types, $additional_mime_types);
    }

    /**
     * Outputs the 'designed by snap media' footer in wordpress admin.
     *
     * @since  1.0.0
     */
    public function branding_admin_footer()
    {
        echo sprintf(
            '%s <a href="http://wordpress.org" target="_blank">WordPress</a> %s <a href="%s" target="_blank">SnapWP</a>',
            __('Built using', 'snap'),
            __('and', 'snap'),
            esc_url(Snap::SNAPWP_HOME)
        );
    }

    /**
     * Flush rewrite rules for custom post types after theme is switched.
     *
     * @since  1.0.0
     */
    public function flush_rewrite_rules()
    {
        flush_rewrite_rules();
    }
}
