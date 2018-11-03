<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static mixed get(string $key, mixed $default = null) Retrieve a config value.
 * @method static boolean has(string $key) Check if a given config value exists.
 * @method static set(string $key, mixed $value) Sets an option.
 * @method static \Snap\Core\Config get_root_instance() Return root Config instance.
 * @method static \void add_default_path(string $path) Add new config directory.
 *
 * @see \Snap\Core\Config
 */
class Config extends Service_Facade
{
    /**
     * Specify the underlying root class.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function get_service_name()
    {
        return \Snap\Core\Config::class;
    }
}
