<?php

namespace Snap\Core;

use Snap\Templating\View;

/**
 * The base Controller class.
 *
 * Ensures all child classes have easy access to Snap\Templating\View
 *
 * @since  1.0.0
 */
class Controller
{
    /**
     * Snap View instance.
     *
     * @since  1.0.0
     * @var View
     */
    protected $view = null;

    /**
     * Snap Container instance.
     *
     * @since  1.0.0
     * @var Container
     */
    protected $container = null;

    /**
     * Set the View instance.
     *
     * @since  1.0.0
     *
     * @param View      $view Templating management.
     * @param Container $container
     */
    public function __construct(View $view, Container $container)
    {
        $this->view = $view;
        $this->container = $container;
    }
}
