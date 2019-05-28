<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static void add(string $key, callable $closure) Add a new service definition.
 * @method static void addInstance($key, $object = null) Add an existing instance to the container.
 * @method static void addSingleton(string $key, callable $closure) Add a new singleton definition.
 * @method static object get(string $key) Return or make an instance from the container.
 * @method static void alias($key, $alias) Alias a service in a container to a different key.
 * @method static void bind($key, $interface) Bind a service in a container to an interface.
 * @method static bool remove($key) Remove a service.
 * @method static object resolve(string $class, array $args = []) Auto-wire and return a class instance.
 * @method static mixed resolveMethod($class, string $method, array $args = []) Auto-wire and run a class method.
 * @method static \Hodl\Container getRootInstance() Return root Container instance.
 *
 * @see \Hodl\Container
 */
class Container extends ServiceFacade
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
        return \Hodl\Container::class;
    }
}
