<?php

namespace Snap\Templating\Blade;

use Illuminate\View\Compilers\ComponentTagCompiler as BaseComponentTagCompiler;

/**
 * Resolves class based components from the theme instead of a Laravel application namespace.
 */
class ComponentTagCompiler extends BaseComponentTagCompiler
{
    /**
     * The namespace class based components are guessed from.
     *
     * @var string
     */
    protected static $component_namespace = 'Theme\\Components\\';

    /**
     * Guess the class name for the given component.
     *
     * @param string $component
     * @return string
     */
    public function guessClassName(string $component)
    {
        return static::$component_namespace . $this->formatClassName($component);
    }

    /**
     * Get the namespace class based components are guessed from.
     *
     * @return string
     */
    public static function getComponentNamespace(): string
    {
        return static::$component_namespace;
    }

    /**
     * Set the namespace class based components are guessed from.
     *
     * The leading slash is omitted, as Laravel adds its own when compiling component classes.
     *
     * @param string $namespace
     */
    public static function setComponentNamespace(string $namespace): void
    {
        static::$component_namespace = \trim($namespace, '\\') . '\\';
    }
}
