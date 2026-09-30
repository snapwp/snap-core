<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;
use Snap\Services\Config;
use WP_Error;
use WP_Http;
use WP_REST_Request;

/**
 * Optionally disables WordPress features which are common attack vectors.
 *
 * Each feature is toggled by its matching theme.* config option.
 */
class Security extends Hookable
{
    /**
     * Error codes from wp_authenticate() which reveal whether a username or email address exists.
     */
    private const REVEALING_LOGIN_ERRORS = ['invalid_username', 'invalid_email', 'incorrect_password'];

    /**
     * Error code used for the generic login error.
     */
    private const GENERIC_LOGIN_ERROR = 'snap_invalid_credentials';

    /**
     * Add the hooks for each enabled toggle.
     */
    public function boot(): void
    {
        if (Config::get('theme.disable_xmlrpc')) {
            $this->disableXmlrpc();
        }

        if (Config::get('theme.disable_user_enumeration')) {
            $this->disableUserEnumeration();
        }

        if (Config::get('theme.disable_application_passwords')) {
            $this->addFilter('wp_is_application_passwords_available', '__return_false');
        }

        if (Config::get('theme.generic_login_errors')) {
            $this->addFilter('authenticate', 'genericLoginErrors', 100);
            $this->addFilter('shake_error_codes', 'addGenericLoginErrorShake');
            $this->addFilter('lostpassword_errors', 'hideUnknownLostPasswordUsers', 99, 2);
        }

        if (Config::get('theme.restrict_rest_api')) {
            $this->addFilter('rest_pre_dispatch', 'restrictRestApi', 10, 3);
        }
    }

    /**
     * Responds to any request to xmlrpc.php with a 403.
     *
     * The xmlrpc_enabled filter alone only disables authenticated methods, leaving system.multicall and pingbacks
     * available, so the endpoint is blocked outright.
     */
    public function blockXmlrpcRequest(): void
    {
        if (!\defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {
            return;
        }

        \status_header(WP_Http::FORBIDDEN);
        \header('Content-Type: text/plain; charset=utf-8');
        echo 'XML-RPC services are disabled on this site.';
        exit;
    }

    /**
     * Remove the X-Pingback header, which advertises the XML-RPC endpoint.
     *
     * @param array $headers The headers to send.
     * @return array
     */
    public function removePingbackHeader(array $headers): array
    {
        unset($headers['X-Pingback']);

        return $headers;
    }

    /**
     * Send a 404 instead of redirecting ?author=N requests to the author archive, which reveals their username.
     */
    public function preventAuthorEnumeration(): void
    {
        if (!$this->isAuthorEnumerationRequest()) {
            return;
        }

        global $wp_query;

        $wp_query->set_404();
        \status_header(WP_Http::NOT_FOUND);
        \nocache_headers();
    }

    /**
     * Stop redirect_canonical sending ?author=N requests on to the author archive.
     *
     * @param string|false $redirect_url The redirect URL.
     * @return string|false
     */
    public function preventAuthorRedirect($redirect_url)
    {
        return $this->isAuthorEnumerationRequest() ? false : $redirect_url;
    }

    /**
     * Remove the users endpoints from the REST API for logged out users.
     *
     * @param array $endpoints The available REST endpoints.
     * @return array
     */
    public function removeUserEndpoints(array $endpoints): array
    {
        if (\is_user_logged_in()) {
            return $endpoints;
        }

        unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);

        return $endpoints;
    }

    /**
     * Remove the users sitemap.
     *
     * @param \WP_Sitemaps_Provider|false $provider The sitemap provider.
     * @param string                      $name     The provider name.
     * @return \WP_Sitemaps_Provider|false
     */
    public function removeUserSitemap($provider, string $name)
    {
        return $name === 'users' ? false : $provider;
    }

    /**
     * Remove author details from oEmbed responses.
     *
     * @param array $data The oEmbed response data.
     * @return array
     */
    public function removeOembedAuthor(array $data): array
    {
        unset($data['author_name'], $data['author_url']);

        return $data;
    }

    /**
     * Replace login errors which reveal whether a user exists with a generic error.
     *
     * @param \WP_User|\WP_Error|null $user The authentication result.
     * @return \WP_User|\WP_Error|null
     */
    public function genericLoginErrors($user)
    {
        if (!$user instanceof WP_Error) {
            return $user;
        }

        if (empty(\array_intersect($user->get_error_codes(), self::REVEALING_LOGIN_ERRORS))) {
            return $user;
        }

        return new WP_Error(
            self::GENERIC_LOGIN_ERROR,
            \__('<strong>Error:</strong> The username, email address or password you entered is incorrect.', 'snap')
        );
    }

    /**
     * Keep the login form shake animation for the generic error.
     *
     * @param array $codes Error codes which shake the login form.
     * @return array
     */
    public function addGenericLoginErrorShake(array $codes): array
    {
        $codes[] = self::GENERIC_LOGIN_ERROR;

        return $codes;
    }

    /**
     * Show the usual "check your email" confirmation when a password reset is requested for an unknown user.
     *
     * Without this, the lost password form reports whether an account exists.
     *
     * @param \WP_Error     $errors    Errors so far.
     * @param \WP_User|false $user_data The user the reset was requested for, or false if not found.
     * @return \WP_Error
     */
    public function hideUnknownLostPasswordUsers(WP_Error $errors, $user_data): WP_Error
    {
        if ($user_data !== false || $errors->has_errors() || !\did_action('login_init')) {
            return $errors;
        }

        \wp_safe_redirect(\add_query_arg('checkemail', 'confirm', \wp_login_url()));
        exit;
    }

    /**
     * Require authentication for REST API requests, except for any whitelisted namespaces.
     *
     * The theme.restrict_rest_api option can be true to restrict all routes, or an array of namespaces
     * (such as 'contact-form-7/v1') which remain public.
     *
     * @param mixed            $result  The response to short-circuit with, or null.
     * @param \WP_REST_Server  $server  The REST server.
     * @param \WP_REST_Request $request The current request.
     * @return mixed
     */
    public function restrictRestApi($result, $server, WP_REST_Request $request)
    {
        // Only external requests are restricted, not internal calls made via rest_do_request().
        if ($result !== null || \is_user_logged_in() || !\defined('REST_REQUEST') || !REST_REQUEST) {
            return $result;
        }

        $route = \ltrim($request->get_route(), '/');

        foreach ((array) Config::get('theme.restrict_rest_api') as $namespace) {
            if (!\is_string($namespace)) {
                continue;
            }

            $namespace = \trim($namespace, '/');

            if ($route === $namespace || \str_starts_with($route, $namespace . '/')) {
                return $result;
            }
        }

        return new WP_Error(
            'rest_not_logged_in',
            \__('You must be logged in to access the REST API.', 'snap'),
            ['status' => \rest_authorization_required_code()]
        );
    }

    /**
     * Add the hooks which disable XML-RPC.
     */
    private function disableXmlrpc(): void
    {
        $this->addFilter('xmlrpc_enabled', '__return_false');
        $this->addFilter('xmlrpc_methods', '__return_empty_array');
        $this->addFilter('wp_headers', 'removePingbackHeader');
        $this->addAction('init', 'blockXmlrpcRequest', 1);
    }

    /**
     * Add the hooks which prevent usernames being discovered.
     */
    private function disableUserEnumeration(): void
    {
        $this->addAction('template_redirect', 'preventAuthorEnumeration', 1);
        $this->addFilter('redirect_canonical', 'preventAuthorRedirect');
        $this->addFilter('rest_endpoints', 'removeUserEndpoints');
        $this->addFilter('wp_sitemaps_add_provider', 'removeUserSitemap', 10, 2);
        $this->addFilter('oembed_response_data', 'removeOembedAuthor');

        // Yoast SEO's author sitemap lists each author's archive URL, which contains their username.
        $this->addFilter('wpseo_sitemap_exclude_author', '__return_empty_array');
    }

    /**
     * Whether the current request is a logged out ?author=N request.
     *
     * @return bool
     */
    private function isAuthorEnumerationRequest(): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking for presence.
        return !\is_admin() && !\is_user_logged_in() && isset($_GET['author']);
    }
}
