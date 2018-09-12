<?php

namespace Theme\Controllers;

use Snap\Core\Ajax;
use Snap\Core\Request;

/**
 * [CLASSNAME description]
 */
class CLASSNAME extends Ajax
{
    /**
  * If true then the AJAX action can be used by all users - logged in and otherwise.
  *
  * When false, only logged in users can call this action.
  *
  * @var boolean
  */
    protected $allow_public_access = true;

    /**
     * Handle the AJAX request.
     *
     * @param Snap\Core\Request $request The current request object.
     */
    public function handle(Request $request)
    {
    }
}
