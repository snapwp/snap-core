<?php

namespace Snap\Services;

/**
 * Allow static access to the Blade service.
 *
 * @method static string file($path, array $data = [], array $mergeData = [])
 * @method static string make($view, $data = [], $mergeData = [])
 * @method static string first(array $views, array $data = [], array $mergeData = [])
 * @method static string renderWhen($condition, string $view, array $data = [], array $mergeData = [])
 * @method static string renderEach($view, $data, $iterator, $empty = 'raw|')
 * @method static bool exists(string $view)
 * @method static \Bladezero\Contracts\View\Engine getEngineFromPath($path)
 * @method static mixed share(string|array $key, $value = null)
 * @method static void addLocation(string $location)
 * @method static \Snap\Templating\Blade\Factory addNamespace(string $namespace, string|array $hints)
 * @method static \Snap\Templating\Blade\Factory prependNamespace(string $namespace, string|array $hints)
 * @method static \Snap\Templating\Blade\Factory replaceNamespace(string $namespace, string|array $hints)
 * @method static void if($name, callable $callback): void
 * @method static void component($path, $alias = null): void
 * @method static void directive($name, callable $handler): void
 * @method static void include($path, $alias = null): void
 * @method static void addExtension($extension, $engine, $resolver = null): void
 * @method static \Bladezero\View\Engines\EngineResolver getEngineResolver()
 * @method static \Bladezero\View\Compilers\BladeCompiler  getCompiler()
 * @method static \Bladezero\View\ViewFinderInterface  getFinder()
 * @method static \Bladezero\Filesystem\Filesystem  getFiles()
 * @method static string getExtension(string $path)
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
