<?php

namespace Snap\Admin;

use Snap\Core\Hookable;
use Snap\Core\Snap;
use Snap\Services\Config;

/**
 * Admin changes for whitelabel/branding purposes.
 */
class Whitelabel extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'admin_footer_text' => 'brandingAdminFooter',
        'login_headerurl' => 'setLoginLogoUrl',
        'update_footer' => [
            99 => 'removeVersionText',
        ],
    ];

    /**
     * Add conditional hooks.
     */
    public function boot()
    {
        if (Config::get('admin.login_extra_css') !== false) {
            $this->addAction('login_enqueue_scripts', 'enqueueLoginCss');
        }

        if (Config::get('admin.login_message') !== false) {
            $this->addFilter('login_message', 'addLoginMessage');
        }
    }

    /**
     * Outputs the SnapWP footer in WordPress admin.
     */
    public function brandingAdminFooter(): void
    {
        if (Config::get('admin.footer_text')) {
            echo \esc_html(Config::get('admin.footer_text'));
            return;
        }

        /** @noinspection HtmlUnknownTarget */
        echo \sprintf(
            '%s %s %s <a href="%s" target="_blank">SnapWP</a>',
            \__('Built using', 'snap'),
            '<a href="https://wordpress.org" target="_blank">WordPress</a>',
            \__('and', 'snap'),
            \esc_url(Snap::SNAPWP_HOME)
        );
    }

    /**
     * Set URL of the login page logo link.
     *
     * @return  string
     */
    public function setLoginLogoUrl(): string
    {
        return Config::get('admin.login_logo_url');
    }

    /**
     * Removes the admin footer version text.
     *
     * @param  ?string $version The current WP version string.
     * @return ?string
     */
    public function removeVersionText(?string $version): ?string
    {
        if (true === Config::get('admin.show_version')) {
            return $version;
        }

        return '';
    }

    /**
     * Enqueue custom login page css.
     */
    public function enqueueLoginCss(): void
    {
        \wp_enqueue_style('theme_custom_login_css', Config::get('admin.login_extra_css'));
    }

    /**
     * Format and output admin.login_message config setting.
     *
     * @return string
     */
    public function addLoginMessage(): string
    {
        return \sprintf('<p class="login-message">%s</p>', (string)Config::get('admin.login_message'));
    }
}
