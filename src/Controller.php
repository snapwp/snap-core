<?php

namespace Snap\Core;

use Snap\Core\Templating\View;

/**
 * The base Controller class.
 *
 * Ensures all child classes have easy access to Snap\Core\Templating\View
 *
 * @since  1.0.0
 */
class Controller
{
    /**
     * Snap View instance.
     *
     * @since  1.0.0
     * @var Snap\Core\Templating\View
     */
    protected $view = null;

    /**
     * Set the View instance.
     *
     * @since  1.0.0
     *
     * @param Snap\Core\Templating\View $view View instance
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }
}
