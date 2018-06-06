<?php

namespace Snap\Core\Templating;

use Snap\Core\Exceptions\TemplatingException;
use Snap\Core\Snap;
use Snap\Core\Request;

/**
 * The basic view class for snap.
 *
 * Renders templates and provides handy methods to reduce repeating common template tasks.
 */
class Partial
{
    /**
     * The parent View.
     *
     * @since  1.0.0
     * @var Snap\Core\View
     */
    private $view;

    /**
     * The global request.
     *
     * @since  1.0.0
     * @var Snap\Core\Request
     */
    private $request;

    private $current_template;

    private $data = [];

    public function __construct(View $view, Request $request)
    {
        $this->view = $view;
        $this->request = $request;
    }

    /**
     * Fetch and display a template partial.
     *
     * @since  1.0.0
     *
     * @throws TemplatingException If no partial template found.
     *
     * @param  string $slug     The slug for the generic template.
     * @param  string $name     Optional. The name of the specialised template.
     * @param  mixed  $data     Optional. Additional data to pass to a partial. Available in the partial as $data.
     *                          It is important to note that nothing is done to destroy/restore the current loop.
     */
    public function render($slug, $name = '', $data = [])
    {
        $partial_template_name = $this->view->get_template_name($slug, $name);
        $this->current_template = $partial_template_name;
        $file_name = locate_template(Snap::config('theme.templates_directory') . '/partials/' . $partial_template_name);

        $this->data = $data;
        
        unset($slug, $name, $data);
        
        if ($file_name === '') {
            throw new TemplatingException('Could not find partial: ' . $partial_template_name);
        }

        require($file_name);
    }

    /**
     * Fetch and display a template partial.
     *
     * It is important to note that nothing is done to destroy/restore the current loop.
     *
     * @since  1.0.0
     *
     * @throws TemplatingException If no partial template found.
     *
     * @param  string $slug The slug for the generic template.
     * @param  string $name Optional. The name of the specialised template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     * @return \Snap\Core\Templating\Partial
     */
    public function partial($slug, $name = '', $data = [])
    {
        $partial = Snap::services()->get(Partial::class);
        $partial->render($slug, $name, $data);
        return $partial;
    }

    /**
     * Wrapper for outputting Pagination.
     *
     * @since 1.0.0
     * @see \Snap\Core\Modules\Pagination
     *
     * @param  array $args Args to pass to the Pagination instance.
     * @return bool|string If $args['echo'] then return true/false if the render is successfull,
     *                     else return the pagination HTML.
     */
    public function pagination($args = [])
    {
        return $this->view->pagination($args);
    }

    /**
     * Runs the standard WP loop, and renders a partial for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy partial choice for each iteration.
     *
     * @since 1.0.0
     *
     * @param string   $partial           Optional. The partial name to render for each post.
     *                                    If null, then defaults to post-type/{post type}.php.
     * @param array    $partial_overrides Optional. An array of overrides.
     *                                    Keys = iteration to apply the override to
     *                                    values = the partial to load instead of $partial.
     *                                    There is also a special key 'alternate', which will load the value on every
     *                                    other iteration.
     * @param WP_Query $wp_query          Optional. An optional custom WP_Query to loop through.
     *                                    Defaults to the global WP_Query instance.
     */
    public function loop($partial = null, $partial_overrides = null, $wp_query = null)
    {
        $this->view->loop($partial, $partial_overrides, $wp_query);
    }

    /**
     * Returns the current view template name.
     *
     * @since 1.0.0
     *
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function get_current_view()
    {
        return $this->view->get_current_view();
    }

    public function __get($name)
    {
        if (\array_key_exists($name, $this->data)) {
            return $this->data[ $name ];
        }

        return null;
    }
}
