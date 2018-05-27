<?php

namespace Snap\Core\Templating;

use Snap\Core\Snap;
use Snap\Core\Exceptions\TemplatingException;
use Snap\Core\Modules\Pagination;
use Snap\Core\Modules\Related_Pages;

/**
 * The basic view class for snap.
 *
 * Renders templates and provides handy methods to reduce repeating common template tasks.
 */
class View
{
    /**
     * The current view name being displayed.
     *
     * @since  1.0.0
     * @var string|null
     */
    private $current_view = null;

    /**
     * Renders a view.
     *
     * @since  1.0.0
     *
     * @throws TemplatingException If no template found.
     * @throws TemplatingException If views are nested.
     *
     * @param  string $slug The slug for the generic template.
     * @param  string $name Optional. The name of the specialised template.
     */
    public function render($slug, $name = '')
    {
        /*
         * When Snap first boots up, it starts the output buffer.
         * Now we have a matched view, we can flush any partials (such as the page <head>).
         */
        \ob_end_flush();

        if ($this->current_view !== null) {
            throw new TemplatingException('Views should not be nested');
        }

        $this->current_view = $this->get_template_name($slug, $name);

        $path = locate_template('templates/views/' . $this->current_view);

        if ($path === '') {
            throw new TemplatingException('Could not find view: ' . $this->current_view);
        }

        require($path);
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
        // Use either the global or supplied WP_Query object.
        if ($wp_query instanceof WP_Query) {
            $wp_query = $wp_query;
        } else {
            global $wp_query;
        }

        $count = 0;

        // Render normal loop using our $wp_query value.
        if ($wp_query->have_posts()) {
            while ($wp_query->have_posts()) {
                $wp_query->the_post();

                // Work out what partial to render.
                if (\is_array($partial_overrides) && isset($partial_overrides[ $count ])) {
                    // An override is present, so load that instead.
                    $this->partial($partial_overrides[ $count ]);
                } elseif (\is_array($partial_overrides)
                    && isset($partial_overrides['alternate'])
                    && $count % 2 !== 0
                ) {
                    // An override is present, so load that instead.
                    $this->partial($partial_overrides['alternate']);
                } elseif ($partial === null) {
                    // Load the default partial for this content type.
                    $this->partial('post-type/' . get_post_type());
                } else {
                    // Load the supplied default partial.
                    $this->partial($partial);
                }

                $count++;
            }
        } else {
            $this->partial('post-type/none');
        }

        wp_reset_postdata();
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
        $pagination = Snap::services()->resolve(
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
     * Generate the template file name from the slug and name.
     *
     * @since 1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  string $name Optional. The name of the specialised template.
     * @return string
     */
    public function get_template_name($slug, $name = '')
    {
        $name = (string) $name;
        $slug = \str_replace([ 'templates/views/', '.php' ], '', $slug);

        $template = "{$slug}.php";

        if ('' !== $name) {
            $template = "{$slug}-{$name}.php";
        }

        return $template;
    }
}
