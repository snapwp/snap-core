<?php

namespace Snap\Core;

use Hodl\Container as Hodl;

class Container extends Hodl
{
    public function resolve_method($class, string $method, array $args = [])
    {
        return parent::resolveMethod($class, $method, $args);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        try {
            $object = parent::get($key);
        } catch (\Exception $e) {
            $object = false;
        }

        return $object;
    }

    /**
     * @param string $key
     * @return null|object
     */
    public function make($key)
    {
        return $this->get($key);
    }


    /**
     * @param      $key
     * @param null $object
     * @throws \Hodl\Exceptions\ContainerException
     */
    public function add_instance($key, $object = null)
    {
        parent::addInstance($key, $object);
    }

    public function add_singleton(string $key, callable $closure)
    {
        parent::addSingleton($key, $closure);
    }
}
