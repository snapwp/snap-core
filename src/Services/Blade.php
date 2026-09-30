<?php

namespace Snap\Services;

/**
 * Allow static access to the Blade service.
 *
 * @method static \Illuminate\Contracts\View\View file($path, array $data = [], array $mergeData = [])
 * @method static \Illuminate\Contracts\View\View make($view, $data = [], $mergeData = [])
 * @method static \Illuminate\Contracts\View\View first(array $views, array $data = [], array $mergeData = [])
 * @method static string renderWhen($condition, string $view, array $data = [], array $mergeData = [])
 * @method static string renderUnless($condition, string $view, array $data = [], array $mergeData = [])
 * @method static string renderEach($view, $data, $iterator, $empty = 'raw|')
 * @method static bool exists(string $view)
 * @method static \Illuminate\Contracts\View\Engine getEngineFromPath($path)
 * @method static mixed share(string|array $key, $value = null)
 * @method static void addLocation(string $location)
 * @method static \Snap\Templating\Blade\Factory addNamespace(string $namespace, string|array $hints)
 * @method static \Snap\Templating\Blade\Factory prependNamespace(string $namespace, string|array $hints)
 * @method static \Snap\Templating\Blade\Factory replaceNamespace(string $namespace, string|array $hints)
 * @method static void if($name, callable $callback)
 * @method static void component($class, $alias = null, $prefix = '')
 * @method static void aliasComponent($path, $alias = null)
 * @method static void directive($name, callable $handler)
 * @method static void include($path, $alias = null)
 * @method static void addExtension($extension, $engine, $resolver = null)
 * @method static void setCsrfHandler(callable $handler)
 * @method static void setAuthHandler(callable $handler)
 * @method static void setCanHandler(callable $handler)
 * @method static void setInjectHandler(callable $handler)
 * @method static void setErrorHandler(callable $handler)
 * @method static \Illuminate\View\Engines\EngineResolver getEngineResolver()
 * @method static \Snap\Templating\Blade\Compiler getCompiler()
 * @method static \Illuminate\View\ViewFinderInterface getFinder()
 * @method static string|null getExtension(string $path)
 * @method static mixed shared($key, $default = null)
 * @method static array getShared()
 *
 * @see \Snap\Templating\Blade\Factory
 */
class Blade
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Templating\Blade\Factory::class;
    }
}
