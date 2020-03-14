<?php

namespace Snap\Templating\Strategies;

use Snap\Exceptions\TemplatingException;
use Snap\Services\Config;
use Snap\Services\Request;
use Snap\Templating\Blade\Factory;
use Snap\Templating\View;
use Snap\Templating\Blade\SnapDirectives;
use Snap\Templating\Blade\WordpressDirectives;
use Snap\Utils\Theme;

/**
 * The default vanilla PHP templating engine.
 */
class DefaultStrategy implements StrategyInterface
{
    use WordpressDirectives;
    use SnapDirectives;

    protected $wordpress_directives = [
        'wphead',
        'wpfooter',
        'sidebar',
        'action',
        'thecontent',
        'theexcerpt',
        'navmenu',
        'searchform',
        'setpostdata',
        'resetpostdata',
    ];

    protected $snap_directives = [
        'simplemenu',
        'endsimplemenu',
        'paginate',
        'loop',
        'endloop',
        'posttypepartial',
    ];

    /**
     * @var \Bladezero\Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $current_view;
    /**
     * @var array
     */
    private $default_data = [];

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;

        $this->addWordpressDirectives();
        $this->addSnapDirectives();
    }

    /**
     * @param string $slug
     * @param array  $data
     * @throws \Snap\Exceptions\TemplatingException
     * @throws \Throwable
     */
    public function render(string $slug, array $data = [])
    {
        $slug = $this->normalizePath($slug);

        if ($this->factory->exists($slug) === false) {
            throw new TemplatingException("Could not find view: $slug");
        }

        $this->current_view = $slug;

        global $wp_query, $post;

        $this->default_data = \array_merge(
            View::getAdditionalData("$slug", $data),
            [
                'wp_query' => $wp_query,
                'request' => Request::getRootInstance(),
                'errors' => Request::getGlobalErrors(),
                'post' => &$post,
                'current_view' => $this->current_view,
            ]
        );

        echo $this->factory->make($slug, $data, $this->default_data);
    }

    /**
     * @param string $slug
     * @param array  $data
     * @throws \Snap\Exceptions\TemplatingException
     * @throws \Throwable
     */
    public function partial(string $slug, array $data = [])
    {
        if ($this->factory->exists($slug) === false) {
            throw new TemplatingException("Could not find partial: $slug");
        }

        echo $this->factory->make($slug, $data, $this->default_data);
    }

    /**
     * @inheritDoc
     */
    public function share($key, $value = null)
    {
        return $this->factory->share($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentView(): ?string
    {
        return $this->current_view;
    }

    /**
     * @inheritDoc
     */
    public function normalizePath(string $path): string
    {
        $path = \str_replace(['\\', Theme::getActiveThemePath('')], ['/', '', ''], $path);

        return \trim(
            \str_replace(
                [Config::get('theme.templates_directory'), '.blade.php', '.php', '/'],
                ['', '', '', '.'],
                $path
            ),
            '.'
        );
    }

    /**
     * Adds any basic WordPress directives to Blade.
     */
    private function addWordpressDirectives()
    {
        foreach ($this->wordpress_directives as $directive) {
            $this->factory->directive($directive, [$this, 'compile' . \ucfirst($directive)]);
        }
    }

    /**
     * Adds any Snap specific directives to Blade.
     */
    private function addSnapDirectives()
    {
        foreach ($this->snap_directives as $directive) {
            $this->factory->directive($directive, [$this, 'compile' . \ucfirst($directive)]);
        }
    }
}
