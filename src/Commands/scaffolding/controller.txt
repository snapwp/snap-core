<?php

namespace Theme\Controllers;

use Snap\Core\Controller;
use Snap\Core\Request;

/**
 * [Controller description]
 */
class CLASSNAME extends Controller
{
    /**
     * [index description]
     *
     * @param  Snap\Core\Request $request The current request object.
     */
    public function index(Request $request)
    {
        $this->view->render('index');
    }
}
