<?php

namespace Snap\Templating;

/**
 * Interface to ensure interoperability between templating engines.
 */
interface TemplatingInterface
{
    /**
     * Renders a view template.
     *
     * @param  string $slug The current slug to render relative to the views directory.
     * @param  array  $data Data to pass to this template.
     * @return void
     */
    public function render($slug, $data = []);
    
    /**
     * Renders a partial template.
     *
     * @param  string $slug The current slug to render relative to the partials directory.
     * @param  array  $data Data to pass to this template.
     * @return void
     */
    public function partial($slug, $data = []);

    /**
     * Should return the parent view for the current request.
     *
     * @return string|null
     */
    public function getCurrentView(): ?string;

    /**
     * Should normalize a provided template path into something the strategy wants to work with.
     *
     * @param string $path The path to transform.
     * @return string
     */
    public function transformPath(string $path): string;
}
