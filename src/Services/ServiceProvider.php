<?php

namespace Snap\Services;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Snap\Core\Concerns\ManagesHooks;

/**
 * Base Provider class.
 */
class ServiceProvider
{
    use ManagesHooks;

    /**
     * List of files and folders published.
     *
     * @var array
     */
    protected static $publishes = [];

    /**
     * Called after all service providers have been registered and are available.
     */
    public function boot()
    {
    }

    /**
     * Register any services into the container.
     */
    public function register()
    {
    }

    /**
     * Returns the package's files to publish.
     *
     * @param  string $type Default null. The subset of files to return.
     * @return array
     */
    public static function getFilesToPublish($type = null)
    {
        if (!isset(static::$publishes[static::class])) {
            return [];
        }

        if ($type !== null) {
            return static::$publishes[static::class][$type];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator(static::$publishes[static::class])
        );

        return \iterator_to_array($iterator);
    }

    /**
     * Register a config location for a package.
     * This config will be overwritten by any matching theme config keys.
     *
     * @param string $path The path to the new config directory.
     */
    protected function addConfigLocation($path)
    {
        Config::addDefaultPath($path);
    }

    /**
     * Register a generic directory to publish.
     *
     * @param  string $source Full path to the source directory.
     * @param  string $target Path to copy the source to - relative to the theme root.
     * @param  string $tag    Optional. The tag to add this directory under.
     */
    protected function publishesDirectory($source, $target, $tag = 'directories')
    {
        static::$publishes[static::class][$tag][$source] = $target;
    }

    /**
     * Register a config directory to publish.
     *
     * @param  string $source Full path to the package config source directory.
     */
    protected function publishesConfig($source)
    {
        static::$publishes[static::class]['config'][$source] = '/config';
    }
}
