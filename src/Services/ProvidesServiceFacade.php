<?php

namespace Snap\Services;

use Snap\Core\Snap;

/**
 * Base Service Facade class.
 */
trait ProvidesServiceFacade
{
    /**
     * Provide static access to the underlying instance methods.
     *
     * @param string $method The method being called.
     * @param array  $args   The method arguments.
     * @return mixed
     *
     * @throws \Hodl\Exceptions\ContainerException If the service name is malformed.
     * @throws \Hodl\Exceptions\NotFoundException When the service does not exist within the container.
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = Snap::getContainer()->get(static::getServiceName());

        static::whenResolving($instance);

        return $instance->{$method}(...$args);
    }

    /**
     * Get the underlying instance from  the container.
     *
     * @return object The resolved instance.
     *
     * @throws \Hodl\Exceptions\ContainerException If the service name is malformed.
     * @throws \Hodl\Exceptions\NotFoundException When the service does not exist within the container.
     */
    public static function getRootInstance()
    {
        return Snap::getContainer()->get(static::getServiceName());
    }


    /**
     * Allows additional actions to be performed whenever the root instance if resolved.
     *
     * @param object $instance The resolved root instance from the container.
     */
    protected static function whenResolving($instance)
    {
    }

    /**
     * Get the name of the service to provide static access to.
     *
     * Must exist within the container.
     *
     * @return string
     */
    abstract protected static function getServiceName(): string;
}
