<?php

namespace Snap\Templating\Strategies;

/**
 * Interface to ensure interoperability between templating engines.
 */
interface StrategyInterface
{
    /**
     * Renders a view template.
     *
     * @param string $slug The current slug to render relative to the views directory.
     * @param array  $data Data to pass to this template.
     * @return void
     */
    public function render(string $slug, array $data = []);

    /**
     * Renders a partial template.
     *
     * @param string $slug The current slug to render relative to the partials directory.
     * @param array  $data Data to pass to this template.
     * @return void
     */
    public function partial(string $slug, array $data = []);

    /**
     * Should return the parent view for the current request.
     *
     * @return string|null
     */
    public function getCurrentView(): ?string;

    /**
     * Add a piece of shared data to the environment.
     *
     * @param array|string $key
     * @param mixed|null   $value
     * @return mixed
     */
    public function share($key, $value = null);

    /**
     * Should normalize a provided template path into something the strategy wants to work with.
     *
     * @param string $path The path to transform.
     * @return string
     */
    public function normalizePath(string $path): string;
}
