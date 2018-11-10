<?php

namespace Snap\Templating\Standard;

use WP_Query;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Templating\Pagination;
use Snap\Templating\View;
use Snap\Templating\Templating_Interface;
use Snap\Exceptions\Templating_Exception;

/**
 * The default vanilla PHP templating engine.
 *
 * @since 1.0.0
 */
class Standard_Strategy implements Templating_Interface
{
    /**
     * The current view name being displayed.
     *
     * @since  1.0.0
     * @var string|null
     */
    private $current_view = null;

    /**
     * Variables to pass to the template and any child partials.
     *
     * @since  1.0.0
     * @var array
     */
    private $data = [];

    /**
     * Holds the current layout to extend.
     *
     * @since  1.0.0
     * @var bool|string
     */
    private $extends = false;

    /**
     * Holds the output of the current view when extending.
     *
     * @since  1.0.0
     * @var string
     */
    private $view = '';

    /**
     * Renders a view.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     *
     * @throws Templating_Exception If views are nested.
     */
    public function render($slug, $data = [])
    {
        if ($this->current_view !== null) {
            throw new Templating_Exception('Views should not be nested');
        }

        $this->current_view = $this->get_template_name($slug);

        global $wp_query;

        $this->data = \array_merge(
            View::get_additional_data("views/$slug", $data),
            [
                'wp_query' => $wp_query,
            ],
            $data
        );

        $snap_template_path = locate_template(Config::get('theme.templates_directory') . '/views/' . $this->current_view);

        if ($snap_template_path === '') {
            throw new Templating_Exception('Could not find view: ' . $this->current_view);
        }

        unset($data, $slug);

        \extract($this->data);

        // Start output buffering in case we are extending a layout.
        \ob_start();

        /**
         * Keep PHPStorm quiet. 
         *
         * @noinspection PhpIncludeInspection
         */
        require $snap_template_path;

        $view = \ob_get_clean();

        if ($this->extends == false) {
            // As we are not extending, just output.
            echo $view;
            return;
        }

        $this->view = $view;
        $this->render_layout();
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

        $data = \array_merge(
            $this->data,
            View::get_additional_data('partials/'.$slug, $data),
            $data
        );

        $partial->render($slug, $data);
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
        if (! $wp_query instanceof WP_Query) {
            global $wp_query;
        }

        $count = 0;

        // Render normal loop using our $wp_query value.
        if ($wp_query->have_posts()) {
            while ($wp_query->have_posts()) {
                $wp_query->the_post();

                $data = [
                    'loop_index' => $count + 1,
                ];

                // Work out what partial to render.
                if (\is_array($partial_overrides) && isset($partial_overrides[ $count ])) {
                    // An override is present, so load that instead.
                    $this->partial($partial_overrides[ $count ], $data);
                } elseif (\is_array($partial_overrides)
                    && isset($partial_overrides['alternate'])
                    && $count % 2 !== 0
                ) {
                    // An override is present, so load that instead.
                    $this->partial($partial_overrides['alternate'], $data);
                } elseif ($partial === null) {
                    // Load the default partial for this content type.
                    $this->partial('post-type/' . get_post_type(), $data);
                } else {
                    // Load the supplied default partial.
                    $this->partial($partial, $data);
                }

                $count++;
            }
        } else {
            $this->partial('post-type/none');
        }

        \wp_reset_postdata();
    }

    /**
     * Wrapper for outputting Pagination.
     *
     * @since 1.0.0
     * @see   \Snap\Templating\Pagination
     *
     * @param  array $args Args to pass to the Pagination instance.
     * @return bool|string If $args['echo'] then return true/false if the render is successful,
     *                     else return the pagination HTML.
     */
    public function pagination($args = [])
    {
        $pagination = Container::resolve(
            Pagination::class,
            [
                'args' => $args,
            ]
        );

        if (isset($args['echo']) && $args['echo'] !== true) {
            return $pagination->get();
        }
        
        return $pagination->render();
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
        return $this->current_view;
    }

    /**
     * Returns whether the current view template extends a layout.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function extends_layout()
    {
        return !$this->extends === false;
    }

    /**
     * Generate the template file name from the slug.
     *
     * @since 1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @return string
     */
    public function get_template_name($slug)
    {
        $slug = \str_replace(
            [
                Config::get('theme.templates_directory') . '/views/', '.php',
                '.'
            ],
            [
                '',
                '/'
            ],
            $slug
        );

        $template = "{$slug}.php";

        return $template;
    }

    /**
     * Sets a layout to extend.
     *
     * @since 1.0.0
     *
     * @param string $layout The name of the layout to extend. Relative to theme.templates_directory config item.
     *
     * @throws Templating_Exception If the current view is trying to extend multiple layouts.
     */
    protected function extends($layout)
    {
        if ($this->extends !== false) {
            throw new Templating_Exception($this->current_view . ' is attempting to extend multiple layouts.');
        }

        $this->extends = $this->get_template_name($layout);
    }

    /**
     * Outputs the current view template within a layout.
     *
     * @since 1.0.0
     */
    protected function output_view()
    {
        echo $this->view;
        $this->view = '';
    }

    /**
     * Render a layout if the current view requires it.
     *
     * @since 1.0.0
     *
     * @throws Templating_Exception
     */
    private function render_layout()
    {
        $snap_layout_path = \locate_template(Config::get('theme.templates_directory') . '/' . $this->extends);

        if ($snap_layout_path === '') {
            throw new Templating_Exception('Could not find layout: ' . $this->extends);
        }

        /**
         * Keep PHPStorm quiet.
         *
         * @noinspection PhpIncludeInspection
         */
        include $snap_layout_path;
    }
}
