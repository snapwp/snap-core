<?php

namespace Snap\Http;

use Snap\Utils\Theme_Utils;
use WP_Http;

/**
 * Handle response headers and redirects.
 */
class Response
{
    /**
     * Redirect the current request to a separate URL.
     *
     * @since 1.0.0
     *
     * @param  string  $url    The destination URL.
     * @param  integer $status Optional. The HTTP status to send. Defaults to 302.
     */
    public function redirect($url, $status = 302)
    {
        if (\wp_redirect($url, $status)) {
            exit;
        }
    }

    /**
     * Redirects the user to a wp-admin URL.
     *
     * After login, the user will be returned to the original URL before redirection took place.
     *
     * @since 1.0.0
     *
     * @param string $path   The path to append to the admin URL.
     * @param int    $status Optional. The HTTP status to send when redirecting. Default 302.
     */
    public function redirect_to_admin($path = null, $status = 302)
    {
        $this->redirect(\admin_url($path), $status);
    }

    /**
     * Redirects the user to the current login URL.
     *
     * After login, the user will be returned to the original URL before redirection took place.
     *
     * @since 1.0.0
     *
     * @param string $redirect_after The URL the user should be sent to after the login screen. Defaults to current URL.
     * @param int    $status Optional. The HTTP status to send when redirecting. Default 302.
     */
    public function redirect_to_login($redirect_after = null, $status = 302)
    {
        if ($redirect_after === null) {
            $redirect_after = Theme_Utils::get_current_url();
        }

        $this->redirect(\wp_login_url($redirect_after), $status);
    }

    /**
     * Aborts the current request and tells WordPress to send a 404 response.
     *
     * Useful in middleware or on privately accessed content.
     *
     * @since 1.0.0
     */
    public function set_404()
    {
        global $wp_query;
        $wp_query->set_404();
        \status_header(WP_Http::NOT_FOUND, 'Content not found');
        \nocache_headers();
    }

    /**
     * Send a JSON success response.
     *
     * @since 1.0.0
     *
     * @param mixed data        Data to JSON encode.
     * @param null                                  $status_code Optional. Status code to send with the response.
     */
    public function send_json_success($data, $status_code = null)
    {
        \wp_send_json_success($data, $status_code);
    }

    /**
     * Send a JSON error response.
     *
     * @since 1.0.0
     *
     * @param mixed data        Data to JSON encode.
     * @param null                                  $status_code Optional. Status code to send with the response.
     */
    public function send_json_error($data, $status_code = null)
    {
        \wp_send_json_error($data, $status_code);
    }
}
