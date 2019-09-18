<?php

namespace Snap\Templating;

use Snap\Services\View as Facade;

/**
 * Deals with rendering templates and passing data to them.
 */
class View
{
    /**
     * The current template rendering strategy.
     *
     * @var TemplatingInterface
     */
    private $strategy = null;

    /**
     * Holds all shared (global) data.
     *
     * @var array
     */
    private static $global_data = [];

    /**
     * Holds all callbacks registered via when().
     *
     * @var array
     */
    private static $composers = [];

    /**
     * Holds all additional data added via addData().
     *
     * @var array
     */
    private static $additional_data = [];

    /**
     * The current template.
     *
     * @var string
     */
    private static $context = null;

    /**
     * Set the current strategy provided by the service container.
     *
     * @param TemplatingInterface $strategy The current template rendering strategy.
     */
    public function __construct(TemplatingInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Adds any global data and dispatches the current strategy's render() method.
     *
     * It is important to note that any data provided to this method takes precedence over global data.
     *
     * @param  string $view The view to render.
     * @param  array  $data An array of data to pass through.
     */
    public function render($view, $data = [])
    {
        $this->strategy->render($view, \array_merge(static::$global_data, $data));
    }

    /**
     * Adds any global data and dispatches the current strategy's partial() method.
     *
     * It is important to note that any data provided to this method takes precedence over global data.
     *
     *
     * @param  string $partial The partial to render.
     * @param  array  $data    An array of data to pass through.
     */
    public function partial($partial, $data = [])
    {
        $this->strategy->partial($partial, \array_merge(static::$global_data, $data));
    }

    /**
     * Adds data to the current context.
     *
     * Must be used within a when() callback.
     *
     * @param string $key   The key of the data to add.
     * @param string $value The data value.
     */
    public function addData($key, $value)
    {
        static::$additional_data[static::$context][$key] = $value;
    }

    /**
     * Returns all shared data which is passed to all templates.
     *
     * @return array
     */
    public function getSharedData(): array
    {
        return static::$global_data;
    }

    /**
     * Gets the current parent view name.
     *
     * @return string
     */
    public function get_current_view(): string
    {
        return $this->strategy->getCurrentView();
    }

    /**
     * Adds data to specific templates only.
     *
     * The callback is run just before the template is rendered, so could feasibly be used for
     * purposes other than adding data.
     *
     * The callback is passed the current view instance, and the current data for the template being rendered.
     *
     *
     * @param  string|array $template The template(s) to add the callback to.
     * @param  callable     $callback The callback function run before the $template is rendered.
     */
    public function when($template, callable $callback)
    {
        if (\is_array($template)) {
            foreach ($template as $current) {
                static::$composers[$this->strategy->transformPath($current)][] = $callback;
            }

            return;
        }

        static::$composers[$this->strategy->transformPath($template)][] = $callback;
    }

    /**
     * Adds shared data which is passed to all templates.
     *
     * @param string|array $key   The key of the data to add.
     *                            Can also be an array of key => values to set multiple data at once.
     * @param mixed        $value Data value if a single key is being added.
     */
    public function addSharedData($key, $value = null)
    {
        if (\is_array($key)) {
            static::$global_data = \array_merge(static::$global_data, $key);
            return;
        }

        static::$global_data[$key] = $value;
    }

    /**
     * Executes any callbacks added via when() for the current template, and returns any additional data
     * registered by the callbacks.
     *
     *
     * @param  string $template The template to fetch the additional data for.
     * @param  array  $data     The data manually passed to the current template.
     * @return array
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     */
    public static function getAdditionalData($template, $data = []): array
    {
        if (isset(static::$composers[$template])) {
            $composers = static::$composers[$template];

            foreach ($composers as $composer) {
                $composer(Facade::getRootInstance()->setContext($template), $data);
            }

            if (isset(static::$additional_data[static::$context])) {
                return static::$additional_data[static::$context];
            }
        }

        return [];
    }

    /**
     * Sets the current template context.
     *
     * @param string $template The template context to set.
     * @return $this
     */
    private function setContext($template)
    {
        static::$context = $template;
        return $this;
    }
}
