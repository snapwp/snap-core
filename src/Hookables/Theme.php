<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;

/**
 * A base class for simplifying declaring theme supports and menus.
 */
class Theme extends Hookable
{
    /**
     * Declare theme support.
     * Keys are the feature to enable, and values are any additional arguments to pass to add_theme_support().
     *
     * @var array
     */
    protected $supports = [
    ];

    /**
     * Declare theme menus.
     *
     * @var array
     */
    protected $menus = [];

    /**
     * Register theme support and menus.
     */
    public function __construct()
    {
        $this->addThemeSupport();
        $this->registerThemeMenus();
    }

    /**
     * Loop through the $supports array, and declare theme support.
     */
    protected function addThemeSupport(): void
    {
        if (empty($this->supports)) {
            return;
        }

        foreach ($this->supports as $feature => $args) {
            if (\is_int($feature)) {
                \add_theme_support($args);
            } else {
                \add_theme_support($feature, $args);
            }
        }
    }

    /**
     * Register the theme's navigation menus.
     */
    protected function registerThemeMenus(): void
    {
        if (empty($this->menus)) {
            return;
        }

        register_nav_menus($this->menus);
    }
}
