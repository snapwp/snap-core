<?php

namespace Snap\Admin;

use Snap\Core\Snap;
use Snap\Core\Hookable;

/**
 * Admin changes for whitelabel/branding purposes.
 *
 * @since  1.0.0
 */
class Whitelabel extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'admin_footer_text' => 'branding_admin_footer',
        'login_headerurl' => 'set_login_logo_url',

        'update_footer' => [
            99 => 'remove_version_text',
        ],
    ];

    /**
     * Add conditional hooks.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        if (Snap::config('admin.login_extra_css') !== false) {
            $this->add_action('login_enqueue_scripts', 'enqueue_login_css');
        }
    }

    /**
     * Outputs the SnapWP footer in WordPress admin.
     *
     * @since  1.0.0
     */
    public function branding_admin_footer()
    {
        if (Snap::config('admin.footer_text')) {
            echo Snap::config('admin.footer_text');
            return;
        }

        echo \sprintf(
            '%s <a href="https://wordpress.org" target="_blank">WordPress</a> %s <a href="%s" target="_blank">SnapWP</a>',
            \__('Built using', 'snap'),
            \__('and', 'snap'),
            \esc_url(Snap::SNAPWP_HOME)
        );
    }

    /**
     * Set URL of the login page logo link.
     *
     * @since  1.0.0
     *
     * @return  string
     */
    public function set_login_logo_url()
    {
        return Snap::config('admin.login_logo_url');
    }

    /**
     * Removes the admin footer version text.
     *
     * @since  1.0.0
     *
     * @param  string $version The current WP version string.
     * @return string
     */
    public function remove_version_text($version)
    {
        if (true === Snap::config('admin.show_version')) {
            return $version;
        }

        return '';
    }

    /**
     * Enqueue custom login page css.
     *
     * @since  1.0.0
     */
    public function enqueue_login_css()
    {
        \wp_enqueue_style('theme_custom_login_css', Snap::config('admin.login_extra_css'));
    }
}
