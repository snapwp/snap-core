<?php

namespace Snap\Request\Middleware;

use Snap\Core\Request;
use Snap\Hookables\Middleware;

/**
 * Some basic middleware.
 *
 * @since  1.0.0
 */
class Is_Logged_In extends Middleware
{
    /**
     * Check if the current user is logged in, and perform the redirect if not.
     *
     * Example: is_logged_in|404
     *
     * @since  1.0.0
     *
     * @param  Request     $request  The current request.
     * @param  string|null $redirect The middleware argument. How to redirect this request.
     * @return boolean
     */
    public function handle(Request $request, $redirect = null)
    {
        if (is_user_logged_in() === true) {
            return true;
        }

        if ($redirect === 'login') {
            $request->redirect_to_login();
        }

        if ($redirect === 'admin') {
            $request->redirect_to_admin();
        }

        if ($redirect !== null) {
            $request->redirect($redirect);
        }

        $request->set_404();

        return false;
    }
}
