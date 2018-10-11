<?php

namespace Snap\Templating;

/**
 * Interface to ensure interopability between templating engines.
 */
interface Templating_Interface
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
     * @return string
     */
    public function get_current_view();
}
