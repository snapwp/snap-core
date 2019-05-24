<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static void render(string $view, array $data = []) Render a view.
 * @method static void partial(string $view, array $data = []) Render a partial.
 * @method static array get_shared_data() Get all global data shared across all views.
 * @method static string get_current_view() Get the current view name.
 * @method static void add_shared_data($key, $value = null) Adds shared data which is passed to all templates.
 * @method static void when($template, callable $callback) Adds data to a specific templates.
 * @method static \Snap\Templating\View getRootInstance() Return root View instance.
 *
 * @see \Snap\Templating\View
 */
class View extends ServiceFacade
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
        return \Snap\Templating\View::class;
    }
}
