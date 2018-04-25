<?php

namespace Snap\Core\Modules;

use Snap\Core\Hookable;

class I18n extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since 1.0.0
     * 
     * @var array
     */
    protected $filters = [
        // register the snap text domain
        'after_setup_theme' => 'load_theme_textdomain',
    ];
    
    /**
     * Register the snap text domain
     */
    public function load_theme_textdomain()
    {
        load_theme_textdomain('snap', get_stylesheet_directory() . '/languages');
    }
}
