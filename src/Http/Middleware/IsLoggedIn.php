<?php

namespace Snap\Http\Middleware;

use Snap\Hookables\Middleware;
use Snap\Http\Response;

/**
 * Some basic middleware.
 */
class IsLoggedIn extends Middleware
{
    /**
     * Check if the current user is logged in, and perform the redirect if not.
     *
     * Example: is_logged_in|login
     *
     * @param \Snap\Http\Response $response
     * @param  string|null        $redirect The middleware argument. How to redirect this request.
     * @return boolean
     */
    public function handle(Response $response, $redirect = null)
    {
        if (\is_user_logged_in() === true) {
            return true;
        }

        if ($redirect === 'login') {
            $response->redirectToLogin();
        }

        if ($redirect === 'admin') {
            $response->redirectToAdmin();
        }

        if ($redirect !== null) {
            $response->redirect($redirect);
        }

        $response->set404();

        return false;
    }
}
