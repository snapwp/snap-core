<?php

namespace Snap\Templating\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as BaseFactory;
use Snap\Services\View;
use Snap\Templating\Blade\Concerns\ProvidesHandlers;

class Factory extends BaseFactory
{
    use ProvidesHandlers;

    /**
     * Get the evaluated view contents for the given path, including any additional data registered for it.
     *
     * @param string $path
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     * @param array  $merge_data
     * @return \Illuminate\Contracts\View\View
     */
    public function file($path, $data = [], $merge_data = [])
    {
        return parent::file($path, $data, $this->withAdditionalData($path, $merge_data));
    }

    /**
     * Get the evaluated view contents for the given view, including any additional data registered for it.
     *
     * @param string $view
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     * @param array  $merge_data
     * @return \Illuminate\Contracts\View\View
     */
    public function make($view, $data = [], $merge_data = [])
    {
        $path = $this->finder->find($this->normalizeName($view));

        return parent::make($view, $data, $this->withAdditionalData($path, $merge_data));
    }

    /**
     * Merge any additional data registered for a view over the merge data.
     *
     * Explicitly passed data always takes precedence over both.
     *
     * @param string $path
     * @param array  $merge_data
     * @return array
     */
    private function withAdditionalData(string $path, array $merge_data): array
    {
        return \array_merge($merge_data, View::getAdditionalData(View::normalizePath($path)));
    }

    /**
     * Get the extension used by the view file.
     *
     * @param string $path
     * @return string|null
     */
    public function getExtension($path)
    {
        return parent::getExtension($path);
    }

    /**
     * Get the Blade compiler instance.
     *
     * @return \Snap\Templating\Blade\Compiler
     */
    public function getCompiler(): BladeCompiler
    {
        return $this->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * Register a handler for custom directives.
     *
     * @param string   $name
     * @param callable $handler
     */
    public function directive($name, callable $handler): void
    {
        $this->getCompiler()->directive($name, $handler);
    }

    /**
     * Register an "if" statement directive.
     *
     * @param string   $name
     * @param callable $callback
     */
    public function if($name, callable $callback): void
    {
        $this->getCompiler()->if($name, $callback);
    }

    /**
     * Register a class-based component alias directive.
     *
     * @param string      $class
     * @param string|null $alias
     * @param string      $prefix
     */
    public function component($class, $alias = null, $prefix = ''): void
    {
        $this->getCompiler()->component($class, $alias, $prefix);
    }

    /**
     * Register a component alias directive.
     *
     * @param string      $path
     * @param string|null $alias
     */
    public function aliasComponent($path, $alias = null): void
    {
        $this->getCompiler()->aliasComponent($path, $alias);
    }

    /**
     * Register an include alias directive.
     *
     * @param string      $path
     * @param string|null $alias
     */
    public function include($path, $alias = null): void
    {
        $this->getCompiler()->include($path, $alias);
    }

    /**
     * Set the namespace class based components are guessed from.
     *
     * @param string $namespace
     */
    public static function setComponentNamespace(string $namespace): void
    {
        ComponentTagCompiler::setComponentNamespace($namespace);
    }
}
