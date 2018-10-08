<?php

namespace Snap\Core;

use Snap\Modules\Assets;

/**
 * A base class for simplifying declaring theme supports and menus.
 *
 * @since  1.0.0
 */
class Application extends Hookable
{
    /**
     * Declare theme support.
     *
     * Keys are the feature to enable, and values are any additional arguments to pass to add_theme_support().
     *
     * @since  1.0.0
     * @var array
     */
    protected $supports = [
        //
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
     * Run immediately after class instantiation.
     *
     * To be overridden by the child class.
     *
     * @since 1.0.0
     */
    protected function boot()
    {
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
                add_theme_support($args);
            } else {
                add_theme_support($feature, $args);
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
}
