<?php

namespace Snap\Hookables;

use ReflectionMethod;
use Snap\Core\Hookable;
use Snap\Routing\MiddlewareQueue;
use Snap\Services\Router;
use Snap\Services\Container;

/**
 * A simple wrapper for auto registering Middleware.
 */
class Middleware extends Hookable
{
    /**
     * The name of the Middleware.
     *
     * If not present, then the snake_case class name is used instead.
     *
     * @var null|string
     */
    protected $name = null;

    /**
     * Run this Hookable only on the frontend.
     *
     * @var boolean
     */
    protected $admin = false;

    /**
     * Boot the AJAX Hookable, and register the handler.
     */
    public function boot()
    {
        MiddlewareQueue::registerMiddleware($this->getName(), [$this, 'handler']);
    }

    /**
     * Auto-wire and call the child class's handle method, injecting any params.
     *
     * @param array $args Params to pass to the handle method.
     * @return mixed
     *
     * @throws \ReflectionException
     */
    public function handler(...$args)
    {
        // Get any expected params of the handle method.
        $ref = new ReflectionMethod($this, 'handle');
        $params = [];

        foreach ($ref->getParameters() as $param) {
            // Let classes get auto-wired.
            if ($param->getClass() === null) {
                if (\count($args) >= 1) {
                    $params[$param->getName()] = \array_shift($args);
                }
            }
        }

        return Container::resolveMethod($this, 'handle', $params);
    }

    /**
     * Return the unqualified snake case name of the current child class, or $name if set.
     *
     * @return string
     */
    private function getName(): string
    {
        if ($this->name === null) {
            return $this->getClassname();
        }

        return $this->name;
    }
}
