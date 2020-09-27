<?php

namespace Snap\Http;

use Snap\Templating\View;
use Snap\Utils\Theme;
use WP_Http;

/**
 * Handle response headers and redirects.
 */
class Response
{
    /**
     * @var \Snap\Templating\View
     */
    protected $view;

    /**
     * Response constructor.
     *
     * @param \Snap\Templating\View $view The global View instance.
     */
    public function __construct(View $view)
    {
        $this->view = $view;

        // Remove the PHP version header for a false sense of security .
        $this->removeHeader('x-powered-by');
    }

    /**
     * Dispatch a view.
     *
     * @param string $view The view to dispatch.
     * @param array  $data Optional. Data to pass to the view.
     */
    public function view(string $view, array $data = []): void
    {
        $this->view->render($view, $data);
    }

    /**
     * Redirect the current request to a separate URL.
     *
     * @param  string  $url    The destination URL.
     * @param  integer $status Optional. The HTTP status to send. Defaults to 302.
     */
    public function redirect(string $url, int $status = 302): void
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
     * @param string|null $path   The path to append to the admin URL.
     * @param int    $status Optional. The HTTP status to send when redirecting. Default 302.
     */
    public function redirectToAdmin(string $path = null, int $status = 302): void
    {
        $this->redirect(\admin_url($path), $status);
    }

    /**
     * Redirects the user to the current login URL.
     *
     * After login, the user will be returned to the original URL before redirection took place.
     *
     * @param string|null $redirect_after The URL the user should be sent to after the login screen. Defaults to current URL.
     * @param int    $status         Optional. The HTTP status to send when redirecting. Default 302.
     */
    public function redirectToLogin(string $redirect_after = null, int $status = 302): void
    {
        if ($redirect_after === null) {
            $redirect_after = Theme::getCurrentUrl();
        }

        $this->redirect(\wp_login_url($redirect_after), $status);
    }

    /**
     * Set an HTTP header.
     *
     * @param string $name  The name of the header to set.
     * @param string $value The value to set for the header.
     * @return $this
     */
    public function setHeader(string $name, string $value): Response
    {
        \header($name . ': ' . $value, true);
        return $this;
    }

    /**
     * Set multiple HTTP headers at once.
     *
     * @param array $headers Array of headers to set.
     * @return $this
     */
    public function withHeaders(array $headers = []): Response
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Remove headers from the response.
     *
     * @param string ...$names Header[s] to remove.
     * @return $this
     */
    public function removeHeader(...$names): Response
    {
        foreach ($names as $name) {
            @\header_remove($name);
        }

        return $this;
    }

    /**
     * Attempt to set the HTTP status header.
     *
     * @param int    $code        The HTTP status code to send.
     * @param string $description The status description.
     * @return $this
     */
    public function setStatus(int $code, string $description = ''): Response
    {
        \status_header($code, $description);
        return $this;
    }

    /**
     * Attempt to set cookie.
     *
     * @param string      $name      The cookie name.
     * @param string      $value     Optional. The cookie value.
     * @param int         $expires   Optional. The cookie lifetime in seconds. Default: 1 hour.
     * @param string      $path      Optional. The path to set the cookie to. Default: '/'.
     * @param string|null $domain    Optional. The domain for the cookie. Default: the current domain.
     * @param bool        $secure    Optional. Whether the cookie is HTTPS only.
     * @param bool        $http_only Optional. Whether the cookie is only accessed via PHP. Set to false to open to js.
     *                               Default: true.
     * @return $this
     */
    public function setCookie(
        string $name,
        $value = '',
        int $expires = 3600,
        string $path = '/',
        string $domain = null,
        bool $secure = null,
        bool $http_only = true
    ): Response {
        $domain = $domain ?: \Snap\Services\Request::getHost();
        $secure = $secure ?: \is_ssl();
        $this->setCookieHeader($name, $value, $expires, $path, $domain, $secure, $http_only);
        return $this;
    }

    /**
     * Attempts to remove a previously set cookie.
     *
     * @param string $name The name of the cookie to unset.
     * @return $this
     */
    public function removeCookie(string $name): Response
    {
        $this->setCookie($name, '', 0);
        return $this;
    }

    /**
     * Aborts the current request and tells WordPress to send a 404 response.
     *
     * Useful in middleware or on privately accessed content.
     */
    public function set404(): void
    {
        global $wp_query;
        $wp_query->set_404();
        \status_header(WP_Http::NOT_FOUND, 'Content not found');
        \nocache_headers();
    }

    /**
     * Send a JSON generic response.
     *
     * @param mixed    $data
     * @param int|null $status_code     Optional. Status code to send with the response.
     * @param bool     $disable_caching Whether to disable browser caching on the response.
     */
    public function json($data = null, int $status_code = null, bool $disable_caching = true): void
    {
        if ($disable_caching) {
            \nocache_headers();
        }

        \wp_send_json($data, $status_code);
        exit;
    }

    /**
     * Send a JSON success response.
     *
     * @param mixed $data            Data to JSON encode.
     * @param int|null  $status_code     Optional. Status code to send with the response.
     * @param bool  $disable_caching Whether to disable browser caching on the response.
     */
    public function jsonSuccess($data = null, int $status_code = null, bool $disable_caching = true): void
    {
        if ($disable_caching) {
            \nocache_headers();
        }

        \wp_send_json_success($data, $status_code);
        exit;
    }

    /**
     * Send a JSON error response.
     *
     * @param mixed $data            Data to JSON encode.
     * @param int|null  $status_code     Optional. Status code to send with the response.
     * @param bool  $disable_caching Whether to disable browser caching on the response.
     */
    public function jsonError($data = null, int $status_code = null, bool $disable_caching = true): void
    {
        if ($disable_caching) {
            \nocache_headers();
        }

        \wp_send_json_error($data, $status_code);
        exit;
    }

    /**
     * Constructs and sends the header to set a cookie.
     *
     * @param string      $name      The cookie name.
     * @param null        $value     The cookie value.
     * @param int         $expires   The cookie lifetime in seconds.
     * @param string      $path      The path to set the cookie to.
     * @param string|null $domain    The domain for the cookie.
     * @param bool        $secure    Whether the cookie is HTTPS only.
     * @param bool        $http_only Whether the cookie is only accessed via PHP. Set to false to open to js.
     */
    private function setCookieHeader(
        string $name,
        $value,
        int $expires,
        string $path,
        string $domain,
        bool $secure,
        bool $http_only
    ): void {
        $attr = [
            \rawurlencode($name) . '=' . \rawurlencode($value),
        ];

        if (!\is_null($expires)) {
            $attr[] = 'Expires=' . \date(DATE_COOKIE, $expires > 0 ? \time() + $expires : 0);
            $attr[] = 'Max-Age=' . $expires;
        }

        if (!\is_null($path)) {
            $attr[] = 'Path=' . $path;
        }

        if (!\is_null($domain)) {
            $attr[] = 'Domain=' . $domain;
        }

        if ($secure) {
            $attr[] = 'Secure';
        }

        if ($http_only) {
            $attr[] = 'HttpOnly';
        }

        \header('Set-Cookie: ' . \implode('; ', $attr), false);
    }
}
