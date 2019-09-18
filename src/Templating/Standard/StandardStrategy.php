<?php

namespace Snap\Templating\Standard;

use Snap\Exceptions\TemplatingException;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Templating\Pagination;
use Snap\Templating\TemplatingInterface;
use Snap\Templating\View;
use WP_Query;

/**
 * The default vanilla PHP templating engine.
 */
class StandardStrategy implements TemplatingInterface
{
    /**
     * The current view name being displayed.
     *
     * @var string|null
     */
    private $current_view = null;

    /**
     * Variables to pass to the template and any child partials.
     *
     * @var array
     */
    private $data = [];

    /**
     * Holds the current layout to extend.
     *
     * @var bool|string
     */
    private $extends = false;

    /**
     * Holds the output of the current view when extending.
     *
     * @var string
     */
    private $view = '';

    /**
     * Renders a view.
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     *
     * @throws \Hodl\Exceptions\ContainerException If Request class not found.
     * @throws \Hodl\Exceptions\NotFoundException If Request class not found.
     * @throws \Snap\Exceptions\TemplatingException If views are nested.
     */
    public function render($slug, $data = [])
    {
        $this->current_view = $this->getTemplateName($slug);

        global $wp_query, $post;

        $this->data = \array_merge(
            View::getAdditionalData("views/$slug", $data),
            [
                'wp_query' => $wp_query,
                'request' => Request::getRootInstance(),
                'errors' => Request::getGlobalErrors(),
                'post' => &$post,
                'current_view' => $this->current_view,
            ],
            $data
        );

        $snap_template_path = \locate_template(
            Config::get('theme.templates_directory') . '/views/' . $this->current_view
        );

        if ($snap_template_path === '') {
            throw new TemplatingException('Could not find view: ' . $this->current_view);
        }

        $this->renderView($snap_template_path, $this->data);
    }

    /**
     * Fetch and display a template partial.
     *
     * It is important to note that nothing is done to destroy/restore the current loop.
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     * @throws \Snap\Exceptions\TemplatingException
     */
    public function partial($slug, $data = [])
    {
        //$partial = Container::get(Partial::class);

        $data = \array_merge(
            $this->data,
            View::getAdditionalData('partials/' . $slug, $data),
            $data
        );

        $snap_template_path = \locate_template(
            Config::get('theme.templates_directory') . '/partials/' . $this->getTemplateName($slug)
        );

        if ($snap_template_path === '') {
            throw new TemplatingException('Could not find partial: ' . $this->getTemplateName($slug));
        }

        $this->renderPartial($snap_template_path, $data);
    }

    /**
     * Runs the standard WP loop, and renders a partial for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy partial choice for each iteration.
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
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     * @throws \Snap\Exceptions\TemplatingException
     */
    public function loop($partial = null, $partial_overrides = null, $wp_query = null)
    {
        if (!$wp_query instanceof WP_Query) {
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
                if (\is_array($partial_overrides) && isset($partial_overrides[$count])) {
                    // An override is present, so load that instead.
                    $this->partial($partial_overrides[$count], $data);
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
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function getCurrentView(): ?string
    {
        return $this->current_view;
    }

    /**
     * Returns whether the current view template extends a layout.
     *
     * @return bool
     */
    public function extendsLayout(): bool
    {
        return !$this->extends === false;
    }

    /**
     * Generate the template file name from the slug.
     *
     * @param  string $slug The slug for the generic template.
     * @return string
     */
    public function getTemplateName($slug): string
    {
        return $this->transformPath($slug) . '.php';
    }

    /**
     * Should normalize a provided template path into something the strategy wants to work with.
     *
     * @param string $path The path to transform.
     * @return string
     */
    public function transformPath(string $path): string
    {
        return \str_replace(
            [Config::get('theme.templates_directory') . '/views/', '.php', '.'],
            ['', '', '/'],
            $path
        );
    }

    /**
     * Sets a layout to extend.
     *
     * @param string $layout The name of the layout to extend. Relative to theme.templates_directory config item.
     *
     * @throws TemplatingException If the current view is trying to extend multiple layouts.
     */
    protected function extends($layout)
    {
        if ($this->extends !== false) {
            throw new TemplatingException($this->current_view . ' is attempting to extend multiple layouts.');
        }
        $this->extends = $this->getTemplateName($layout);
    }

    /**
     * Outputs the current view template within a layout.
     */
    protected function outputView()
    {
        echo $this->view;
        $this->view = '';
    }

    /**
     * Render a layout if the current view requires it.
     *
     * @throws TemplatingException If the layout could not be found.
     */
    private function renderLayout()
    {
        $snap_layout_path = \locate_template(Config::get('theme.templates_directory') . '/' . $this->extends);

        if ($snap_layout_path === '') {
            throw new TemplatingException('Could not find layout: ' . $this->extends);
        }

        /** @noinspection PhpIncludeInspection */
        include $snap_layout_path;
    }

    /**
     * @param string $snap_template_path
     * @param array  $data
     * @throws \Snap\Exceptions\TemplatingException
     */
    private function renderView(string $snap_template_path, array $data = [])
    {
        \extract($data);
        unset($data);

        // Start output buffering in case we are extending a layout.
        \ob_start();

        /** @noinspection PhpIncludeInspection */
        require $snap_template_path;

        $view = \ob_get_clean();

        if ($this->extends === false) {
            // As we are not extending, just output.
            echo $view;
            return;
        }

        $this->view = $view;
        $this->renderLayout();
    }

    /**
     * @param string $snap_template_path
     * @param array  $data
     */
    private function renderPartial(string $snap_template_path, array $data = [])
    {
        \extract($data);
        unset($data);

        /** @noinspection PhpIncludeInspection */
        require $snap_template_path;
    }
}
