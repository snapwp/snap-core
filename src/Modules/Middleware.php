<?php

namespace Snap\Modules;

use Snap\Core\Hookable;
use Snap\Core\Request;

/**
 * Some basic middleware.
 *
 * @since  1.0.0
 */
class Middleware extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since 1.0.0
     * @var array
     */
    protected $filters = [
        'snap_middleware_is_logged_in' => 'is_logged_in',
    ];

    /**
     * Check if the current user is logged in, and perform the redirect if not.
     *
     * Example: is_logged_in|404
     *
     * @since  1.0.0
     *
     * @param  Request      $request  The current request.
     * @param  string|null  $redirect The middleware argument. How to redirect this request.
     * @return boolean
     */
    public function is_logged_in(Request $request, $redirect = null)
    {
        if (is_user_logged_in()) {
            return true;
        }

        if ($redirect === 'login') {
            Request::redirect_to_login();
        }

        if ($redirect === '404') {
            Request::set_404();
        }

        if ($redirect === 'admin') {
            Request::redirect_to_admin();
        }

        if ($redirect !== null) {
            Request::redirect($redirect);
        }

        return false;
    }
}
