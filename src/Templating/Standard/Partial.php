<?php

namespace Snap\Templating\Standard;

use Snap\Exceptions\Templating_Exception;
use Snap\Services\Config;
use Snap\Services\Container;

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
     * @var \Snap\Templating\View
     */
    protected $view;

    /**
     * Variables to pass to the template and any child partials.
     *
     * @since  1.0.0
     * @var array
     */
    protected $data = [];

    /**
     * The current view name.
     *
     * @since  1.0.0
     * @var string
     */
    protected $current_template;

    /**
     * Constructor. Set reference to the parent view.
     *
     * @param Standard_Strategy $view The parent view.
     */
    public function __construct(Standard_Strategy $view)
    {
        $this->view = $view;
    }

    /**
     * Fetch and display a template partial.
     *
     * @since  1.0.0
     *
     * @throws Templating_Exception If no partial template found.
     *
     * @param  string $slug     The slug for the generic template.
     * @param  mixed  $data     Optional. Additional data to pass to a partial. Available in the partial as $data.
     *                          It is important to note that nothing is done to destroy/restore the current loop.
     */
    public function render($slug, $data = [])
    {
        $this->current_template = $this->view->get_template_name($slug);
        
        $snap_template_path = locate_template(
            Config::get('theme.templates_directory') . '/partials/' . $this->view->get_template_name($slug)
        );

        $this->data = $data;

        if ($snap_template_path === '') {
            throw new Templating_Exception('Could not find partial: ' . $this->view->get_template_name($slug));
        }

        unset($slug, $name, $data);

        \extract($this->data);

        /**
         * Keep PHPStorm quiet.
         *
         * @noinspection PhpIncludeInspection
         */
        require($snap_template_path);
    }

    /**
     * Fetch and display a template partial.
     *
     * It is important to note that nothing is done to destroy/restore the current loop.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     */
    public function partial($slug, $data = [])
    {
        $partial = Container::get(Partial::class);
        $data = \array_merge($this->data, $data);
        $partial->render($slug, $data);
    }

    /**
     * Wrapper for outputting Pagination.
     *
     * @since 1.0.0
     * @see   Pagination
     *
     * @param  array $args Args to pass to the Pagination instance.
     * @return bool|string If $args['echo'] then return true/false if the render is successful,
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
     * @param string    $partial           Optional. The partial name to render for each post.
     *                                     If null, then defaults to post-type/{post type}.php.
     * @param array     $partial_overrides Optional. An array of overrides.
     *                                     Keys = iteration to apply the override to
     *                                     values = the partial to load instead of $partial.
     *                                     There is also a special key 'alternate', which will load the value on every
     *                                     other iteration.
     * @param \WP_Query $wp_query         Optional. An optional custom WP_Query to loop through.
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

    /**
     * Returns the current view template name.
     *
     * @since 1.0.0
     *
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function extends_layout()
    {
        return $this->view->extends_layout();
    }
}
