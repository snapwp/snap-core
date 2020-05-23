<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;

class I18n extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        // register the snap text domain
        'after_setup_theme' => 'loadThemeTextdomain',
    ];
    
    /**
     * Register the snap text domain
     */
    public function loadThemeTextdomain()
    {
        \load_theme_textdomain('snap', \get_stylesheet_directory() . '/languages');
    }
}
