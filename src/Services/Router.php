<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static \Snap\Core\Router dispatch(string $controller) Dispatch a controller action.
 * @method static \Snap\Core\Router when(bool|callable $result) A test to see if a route passes.
 * @method static \Snap\Core\Router not(bool|callable $result) A negative test to see if a route passes.
 * @method static \Snap\Core\Router view(string $view) Render a view and stop any other routes from processing.
 * @method static \Snap\Core\Router using(string|array $middleware) Apply middleware.
 * @method static \Snap\Core\Router group(Callable $callback) Create a route group.
 * @method static \Snap\Core\Router getRootInstance() Return root Router instance.
 *
 * @see \Snap\Core\Router
 */
class Router extends ServiceFacade
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
        return \Snap\Core\Router::class;
    }

    /**
     * Allows additional actions to be performed whenever the root instance if resolved.
     *
     * @since 1.0.0
     *
     * @param \Snap\Core\Router $instance
     */
    protected static function whenResolving($instance)
    {
        $instance->reset();
    }
}
