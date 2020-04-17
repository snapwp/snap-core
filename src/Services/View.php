<?php

namespace Snap\Services;

/**
 * Allow static access to the View service.
 *
 * @method static void render(string $view, array $data = []) Render a view.
 * @method static void partial(string $view, array $data = []) Render a partial.
 * @method static string getCurrentView() Get the current view name.
 * @method static mixed share($key, $value = null) Adds shared data which is passed to all templates.
 * @method static void when($template, callable $callback) Adds data to a specific templates.
 * @method static array getAdditionalData($template, $data = []) Return any view data added via view composers.
 * @method static string normalizePath(string $path) Normalizes a template path according to the current strategy.
 * @method static \Snap\Templating\View getRootInstance() Return root View instance.
 *
 * @see \Snap\Templating\View
 */
class View
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Templating\View::class;
    }
}
