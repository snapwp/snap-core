<?php

namespace Snap\Core;

/**
 * A base class for simplifying declaring theme supports and menus.
 *
 * @since  1.0.0
 */
class Application extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'widgets_init' => 'register_theme_widgets',
        'wp_enqueue_scripts' => 'enqueue_theme_assets',
    ];

    /**
     * Declare theme support.
     *
     * Keys are the feature to enable, and values are any additional arguments to pass to add_theme_support().
     *
     * @since  1.0.0
     * @var array
     */
    protected $supports = [
    ];

    /**
     * Declare theme menus.
     *
     * @since  1.0.0
     * @var array
     */
    protected $menus = [
        'primary' => 'The primary navigation for the site',
    ];

    /**
     * Register theme support and menus.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->add_theme_support();
        $this->register_theme_menus();
    }

    /**
     * Loop through the $supports array, and declare theme support.
     *
     * @since  1.0.0
     */
    protected function add_theme_support()
    {
        if (empty($this->supports)) {
            return;
        }

        foreach ($this->supports as $feature => $args) {
            if (\is_integer($feature)) {
                \add_theme_support($args);
            } else {
                \add_theme_support($feature, $args);
            }
        }
    }

    /**
     * Register the theme's navigation menus.
     *
     * @since 1.0.0
     */
    protected function register_theme_menus()
    {
        if (empty($this->menus)) {
            return;
        }

        register_nav_menus($this->menus);
    }

    /**
     * Enqueue the theme CSS files.
     *
     * @since 1.0.0
     */
    public function enqueue_theme_assets()
    {
    }

    /**
     * Register the theme's widgets.
     *
     * @since 1.0.0
     */
    public function register_theme_widgets()
    {
    }
}
