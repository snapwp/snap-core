<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static mixed get(string $key, mixed $default = null) Retrieve a config value.
 * @method static boolean has(string $key) Check if a given config value exists.
 * @method static set(string $key, mixed $value) Sets an option.
 * @method static \Snap\Core\Config getRootInstance() Return root Config instance.
 * @method static void addDefaultPath(string $path) Add new config directory.
 *
 * @see \Snap\Core\Config
 */
class Config extends ServiceFacade
{
    /**
     * Specify the underlying root class.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function getServiceName()
    {
        return \Snap\Core\Config::class;
    }
}
