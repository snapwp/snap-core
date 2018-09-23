<?php

namespace Snap\Services;

use Snap\Core\Snap;

/**
 * Base Provider class.
 */
class Provider implements Interfaces\Provider
{
    /**
     * List of files and folders published.
     *
     * @since 1.0.0
     * @var array
     */
    public static $publishes = [
        'directories' => [],
    ];

    /**
     * Called after all service providers have been registered and are available.
     *
     * @since 1.0.0
     */
    public function boot()
    {
    }

    /**
     * Register any services into the container.
     *
     * @since 1.0.0
     */
    public function register()
    {
    }

    /**
     * Register a config location for a service.
     *
     * This config will be overwritten by any matching theme config keys.
     *
     * @since 1.0.0
     *
     * @param string $path The path to the new config directory.
     */
    protected function add_config_location($path)
    {
        Snap::config()->add_default_path($path);
    }

    /**
     * Register a generic directory to publish.
     *
     * @since  1.0.0
     *
     * @param  string $source Full path to the source directory.
     * @param  string $target Path to copy the source to - relative to the theme root.
     */
    protected function publishes_directory($source, $target)
    {
        static::$publishes['directories'][ $source ] = $target;
    }

    /**
     * Register a config directory to publish.
     *
     * @since  1.0.0
     *
     * @param  string $source Full path to the package config source directory.
     */
    protected function publishes_config($source)
    {
        static::$publishes['directories'][ $source ] = '/config';
    }
}
