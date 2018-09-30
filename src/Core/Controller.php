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
     * @var Snap\Templating\View
     */
    protected $view = null;

    /**
     * Set the View instance.
     *
     * @since  1.0.0
     *
     * @param View $view Templating management.
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }
}
