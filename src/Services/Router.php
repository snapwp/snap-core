<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static void dispatchPostTemplate()
 * @method static void dispatch(string|array $controller)
 * @method static void view(string $view, array $data)
 * @method static \Snap\Routing\Router when(bool|callable $result)
 * @method static \Snap\Routing\Router not(bool|callable $result)
 * @method static \Snap\Routing\Router whenPostTemplate(string $template = null)
 * @method static \Snap\Routing\Router using(string|array $middleware)
 * @method static \Snap\Routing\Router group(Callable $callback)
 * @method static \Snap\Routing\Router namespace(string $namespace)
 * @method static \Snap\Routing\Router url(string $url)
 * @method static \Snap\Routing\Router where(array $map)
 * @method static \Snap\Routing\Router get()
 * @method static \Snap\Routing\Router post()
 * @method static \Snap\Routing\Router put()
 * @method static \Snap\Routing\Router patch()
 * @method static \Snap\Routing\Router delete()
 * @method static \Snap\Routing\Router any(string[] ...$methods)
 * @method static array getRouteParams()
 * @method static \Snap\Routing\Router getRootInstance()
 *
 * @see \Snap\Routing\Router
 */
class Router
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Routing\Router::class;
    }

    /**
     * Allows additional actions to be performed whenever the root instance if resolved.
     *
     * @param \Snap\Routing\Router $instance
     */
    protected static function whenResolving(\Snap\Routing\Router $instance): void
    {
        $instance->reset();
    }
}
