<?php

namespace Snap\Core;

use WP_Http;
use Snap\Core\Request\Bag;

/**
 * Gathers all request variables into one place and provides a simple API for changes affecting the response.
 * 
 * @since 1.0.0
 */
class Request
{
    /**
     * Request query params.
     *
     * @since 1.0.0
     * @var Snap\Core\Request\Bag
     */
    public $query = null;

    /**
     * Request post params.
     * @since 1.0.0
     * 
     * @var Snap\Core\Request\Bag
     */
    public $post = null;

    /**
     * Request server params.
     *
     * @since 1.0.0
     * @var Snap\Core\Request\Bag
     */
    public $server = null;

    /**
     * Holds both POST and GET params when both are present.
     *
     * POST takes precedence.
     *
     * @since 1.0.0
     * @var Snap\Core\Request\Bag
     */
    public $request = null;

    /**
     * The current request URL.
     *
     * @since 1.0.0
     * @var string
     */
    public $url;

    /**
     * The current request path.
     *
     * @since 1.0.0
     * @var string
     */
    public $path;

    /**
     * The current request scheme.
     *
     * @since 1.0.0
     * @var string
     */
    public $scheme;

    /**
     * The current query being run by WordPress.
     *
     * @since 1.0.0
     * @var string
     */
    public $matched_query;

    /**
     * The current rewrite rule being run.
     *
     * @since 1.0.0
     * @var string
     */
    public $matched_rule;

    /**
     * Whether WordPress thinks the current request is from a mobile.
     *
     * @since 1.0.0
     * @var boolean
     */
    public $is_mobile = false;

    /**
     * Redirect the current request to a seperate URL.
     *
     * @since 1.0.0
     *
     * @param  string  $url    The destination URL.
     * @param  integer $status Optional. The HTTP status to send. Defaults to 302.
     */
    public function redirect($url, $status = 302)
    {
        if (wp_redirect($url, $status)) {
            exit;
        }
    }

    /**
     * Redirects the user to the current login URL.
     *
     * After login, the user will be returned to the original URL before redirection took place.
     *
     * @since 1.0.0
     *
     * @param string $redirect_after The URL the user should be sent to after the login screen. Defaults to current URL.
     */
    public function redirect_to_login($redirect_after = null)
    {
        if ($redirect_after === null) {
            $redirect_after = Utils::get_current_url();
        }

        self::redirect(wp_login_url($redirect_after), 302);
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
        status_header(WP_Http::NOT_FOUND);
        nocache_headers();
    }

    public function __construct()
    {
        $this->populate_server();
        $this->populate_query();
        $this->populate_post();
        $this->populate_request();
        $this->populate_properties();
    }

    /**
     * Get the request HTTP method.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_method()
    {
        return $this->server('REQUEST_METHOD');
    }

    /**
     * Returns the path segments.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_path_segments()
    {
        return array_filter(explode('/', $this->path));
    }

    public function is_method($method)
    {
        return strtoupper($method) === $this->get_method();
    }

    public function request($key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    public function server($key, $default = null)
    {
        return $this->server->get($key, $default);
    }

    public function post($key, $default = null)
    {
        return $this->post->get($key, $default);
    }

    public function query($key, $default = null)
    {
        return $this->query->get($key, $default);
    }




    private function populate_request()
    {
        if ($this->get_method() === 'GET') {
            $this->request = new Bag($this->query->to_array());
        } else {
            $this->request = new Bag(
                array_merge($this->query->to_array(), $this->post->to_array())
            );
        }
    }
    private function populate_query()
    {
        $this->query = new Bag($_GET);
    }

    private function populate_post()
    {
        $this->post = new Bag($_POST);
    }

    private function populate_properties()
    {
        global $wp;

        $this->is_mobile = (bool) wp_is_mobile();
        $this->scheme = is_ssl() ? 'https' : 'http';

        $this->matched_query = $wp->matched_query;
        $this->matched_rule = $wp->matched_rule;

        $this->url = Utils::get_current_url();

        $this->path = rtrim(parse_url($this->url, PHP_URL_PATH), '/');
    }

    private function populate_server()
    {
        $definition = [
            'REQUEST_METHOD' => [
                'filter'  => FILTER_CALLBACK,
                'options' => function ($method) {
                    return strtoupper(filter_var($method, FILTER_SANITIZE_STRING));
                }
            ],
            'QUERY_STRING'    => FILTER_UNSAFE_RAW,
            'REMOTE_ADDR'     => FILTER_VALIDATE_IP,
            'SERVER_PORT'     => FILTER_SANITIZE_NUMBER_INT,
            'SERVER_NAME'     => FILTER_SANITIZE_STRING,
            'HTTP_HOST'       => FILTER_SANITIZE_URL,
            'HTTP_REFERER'    => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => FILTER_SANITIZE_STRING
        ];

        $server = filter_input_array(INPUT_SERVER, $definition);

        $this->server = new Bag($server);
    }
}
