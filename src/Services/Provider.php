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
    protected static $publishes = [
        'directories' => [],
    ];

    /**
     * Called after all service providers have been registered and are available.
     *
     * @since 1.0.0
     */
    public function boot() {}

    /**
     * Register any services into the container.
     *
     * @since 1.0.0
     */
    public function register() {} 

    /**
     * Returns the package's files to publish.
     *
     * @since  1.0.0
     * 
     * @param  string $type Default null. The subset of files to return.
     * @return array
     */
    public static function get_files_to_publish($type = null)
    {
        if ($type !== null) {
            return static::$publishes[$type];
        }
        
        return static::$publishes;
    }

    /**
     * Register a config location for a package.
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
