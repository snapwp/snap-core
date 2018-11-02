<?php

namespace Snap\Services;

use RuntimeException;
use Snap\Core\Snap;

/**
 * Base Service Facade class.
 *
 * @since 1.0.0
 */
abstract class Service_Facade
{
    /**
     * Holds all resolved facade instances.
     *
     * @since 1.0.0
     * @var array
     */
    protected static $instances = [];


    /**
     * Get the underlying instance from  the container.
     *
     * @since 1.0.0
     *
     * @return object The resolved instance.
     */
    public static function get_root_instance()
    {
        if (! isset(static::$instances[ static::get_service_name() ])) {
            static::resolve_service();
        }

        return static::$instances[ static::get_service_name() ];
    }

    /**
     * Provide static access to the underlying instance methods.
     *
     * @since 1.0.0
     *
     * @param string $method The method being called.
     * @param array  $args   The method arguments.
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (! isset(static::$instances[ static::get_service_name() ])) {
            static::resolve_service();
        }

        static::when_resolving(static::$instances[ static::get_service_name() ]);

        return static::$instances[ static::get_service_name() ]->{$method}(...$args);
    }

    /**
     * Resolve the underlying instance from the service container.
     *
     * @since 1.0.0
     */
    protected static function resolve_service()
    {
        static::$instances[ static::get_service_name() ] = Snap::get_container()->get(static::get_service_name());
    }

    /**
     * Allows additional actions to be performed whenever the root instance if resolved.
     *
     * @since 1.0.0
     *
     * @param object $instance The resolved root instance from the container.
     */
    protected static function when_resolving($instance)
    {
    }

    /**
     * Get the name of the service to provide static access to.
     *
     * Must exist within the container.
     *
     * @since 1.0.0
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function get_service_name()
    {
        throw new RuntimeException('Service Facade must implement get_service_name method.');
    }
}
