<?php

namespace Snap\Services;

use Snap\Core\Concerns\Manages_Hooks;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;

/**
 * Base Provider class.
 */
class Service_Provider
{
    use Manages_Hooks;

    /**
     * List of files and folders published.
     *
     * @since 1.0.0
     * @var array
     */
    protected static $publishes = [];

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
     * Returns the package's files to publish.
     *
     * @since  1.0.0
     *
     * @param  string $type Default null. The subset of files to return.
     * @return array
     */
    public static function get_files_to_publish($type = null)
    {
        if (!isset(static::$publishes[ static::class ])) {
            return [];
        }

        if ($type !== null) {
            return static::$publishes[ static::class ][ $type ];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator(static::$publishes[ static::class ])
        );

        return \iterator_to_array($iterator);
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
        Config::add_default_path($path);
    }

    /**
     * Register a generic directory to publish.
     *
     * @since  1.0.0
     *
     * @param  string $source Full path to the source directory.
     * @param  string $target Path to copy the source to - relative to the theme root.
     * @param  string $tag    Optional. The tag to add this directory under.
     */
    protected function publishes_directory($source, $target, $tag = 'directories')
    {
        static::$publishes[ static::class ][ $tag ][ $source ] = $target;
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
        static::$publishes[ static::class ]['config'][ $source ] = '/config';
    }
}
