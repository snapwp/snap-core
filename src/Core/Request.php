<?php

namespace Snap\Core;

use WP_Http;
use ArrayAccess;
use Snap\Request\Bag;
use Snap\Services\Container;
use Snap\Utils\Theme_Utils;

/**
 * Gathers all request variables into one place, and provides a simple API for changes affecting the response.
 *
 * @since 1.0.0
 */
class Request implements ArrayAccess
{
    /**
     * Request query params.
     *
     * @since 1.0.0
     * @var Bag
     */
    public $query = null;

    /**
     * Request post params.
     *
     * @since 1.0.0
     * @var Bag
     */
    public $post = null;

    /**
     * Request server params.
     *
     * @since 1.0.0
     * @var Bag
     */
    public $server = null;

    /**
     * Holds both POST and GET params when both are present.
     *
     * POST takes precedence.
     *
     * @since 1.0.0
     * @var Bag
     */
    public $request = null;

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
     * The Validator instance.
     *
     * @since 1.0.0
     * @var \Rakit\Validation\Validator|\Rakit\Validation\Validation
     */
    public $validation = null;

    /**
     * The current request URL.
     *
     * @since 1.0.0
     * @var string
     */
    protected $url;

    /**
     * The current request path.
     *
     * @since 1.0.0
     * @var string
     */
    protected $path;

    /**
     * The current request scheme.
     *
     * @since 1.0.0
     * @var string
     */
    protected $scheme;

    /**
     * Populate request variables and properties.
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        $this->populate_server();
        $this->populate_query();
        $this->populate_post();
        $this->populate_request();
        $this->populate_properties();
        $this->set_validation();
    }

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
        if (wp_redirect($url, $status)) {
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
        $this->redirect(admin_url($path), $status);
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

        $this->redirect(wp_login_url($redirect_after), $status);
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
        status_header(WP_Http::NOT_FOUND, 'Content not found');
        nocache_headers();
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
     * Returns the current URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     * Returns the current URL path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_scheme()
    {
        return $this->scheme;
    }

    /**
     * Returns the current URL path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_path()
    {
        return $this->path;
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
        return \array_values(\array_filter(\explode('/', $this->path)));
    }

    /**
     * Checks if the current request matches the supplied HTTP method.
     *
     * @since  1.0.0
     *
     * @param  string $method HTTP method to check against. Case insensitive.
     * @return boolean
     */
    public function is_method($method)
    {
        return \strtoupper($method) === $this->get_method();
    }

    /**
     * Returns a parameter from the request bag, or a default if not present.
     *
     * @since  1.0.0
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    /**
     * Returns a parameter from the server bag, or a default if not present.
     *
     * @since  1.0.0
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function server($key, $default = null)
    {
        return $this->server->get($key, $default);
    }

    /**
     * Returns a parameter from the post bag, or a default if not present.
     *
     * @since  1.0.0
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function post($key, $default = null)
    {
        return $this->post->get($key, $default);
    }

    /**
     * Returns a parameter from the query bag, or a default if not present.
     *
     * @since  1.0.0
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function query($key, $default = null)
    {
        return $this->query->get($key, $default);
    }

    /**
     * Set the validation error messages.
     *
     * @since  1.0.0
     * @see  https://github.com/rakit/validation#custom-validation-message for format.
     *
     * @param array $messages Error messages as key value pairs.
     * @return Request
     */
    public function set_error_messages(array $messages = [])
    {
        $this->validation->setMessages($messages);

        return $this;
    }

    /**
     * Set the validation rules.
     *
     * @since  1.0.0
     * @see  https://github.com/rakit/validation#available-rules for format.
     *
     * @param array $rule_set Rules as key value pairs.
     * @return Request
     */
    public function set_rules(array $rule_set = [])
    {
        foreach ($rule_set as $attribute_key => $rules) {
            $this->validation->addAttribute($attribute_key, $rules);
        }

        return $this;
    }

    /**
     * Set aliases for use in your error messages.
     *
     * In error messages :attribute can be used to substitute with the input array key into the message.
     * The key might not be ideal, so you can provide a better substitute as an alias.
     *
     * @since  1.0.0
     *
     * @param array $aliases Key value pairs as original => alias.
     */
    public function set_aliases(array $aliases = [])
    {
        $this->validation->setAliases($aliases);
    }

    /**
     * A shorthand to send wp error or success JSON responses based on validation status.
     *
     * Will use the messages, and rules as added via set_errors()/set_messages() if no overrides are present.
     * Should be used if you need to set aliases.
     *
     * @since  1.0.0
     *
     * @param  array $rules Optional. Rules to use. Defaults to rules set via set_rules().
     * @param  array $messages Optional. Messages to use. Defaults to rules set via set_messages().
     */
    public function validate_ajax_request(array $rules = [], array $messages = [])
    {
        // Validation is not using $this->validation so overwrite rules and messages.
        if ($rules !== []) {
            $validation = $this->validate_data($this->request->to_array(), $rules, $messages);
            
            if ($validation === true) {
                wp_send_json_success('Success');
            }

            wp_send_json_error($validation, 400);
        } else {
            if ($this->is_valid()) {
                wp_send_json_success($this->validation->getValidatedData());
            }

            wp_send_json_error($this->get_errors(), 400);
        }
    }

    /**
     * Validates the request using the rules and messages set on the internal validation instance.
     *
     * @since  1.0.0
     *
     * @return boolean If the validation passed or not.
     */
    public function is_valid()
    {
        $this->validation->validate();

        return ! $this->validation->fails();
    }

    /**
     * Get errors from the internal validation instance as an array.
     *
     * @since  1.0.0
     *
     * @return array Errors.
     */
    public function get_errors()
    {
        return $this->validation->errors()->toArray();
    }

    /**
     * Manually validate some data not submitted via POST, FILES, or GET.
     *
     * @since  1.0.0
     *
     * @param  array $inputs The array of data to validate as key value pairs.
     * @param  array $rules The rules to run against the data.
     * @param  array $messages Messages to display when a value fails.
     * @return bool|array Returns true if data validates, or an array of error messages.
     */
    public function validate_data(array $inputs, array $rules = [], array $messages = [])
    {
        /** @var \Rakit\Validation\Validation $validation */
        $validation = Container::get('Rakit\Validation\Validator')->validate($inputs, $rules, $messages);

        if ($validation->fails()) {
            return $validation->errors()->toArray();
        }

        return true;
    }


    /**
     * Set the internal instance of the Validator.
     *
     * @since  1.0.0
     */
    private function set_validation()
    {
        $validator = Container::get('Rakit\Validation\Validator');

        $this->validation = $validator->make(
            $this->request->to_array(),
            []
        );
    }

    /**
     * Creates and fills the request bag with query params, and $_POST params if present.
     *
     * Post parameters take precedence and overwrite query params of the same key.
     *
     * @since  1.0.0
     */
    private function populate_request()
    {
        if ($this->get_method() === 'GET') {
            $this->request = new Bag($this->query->to_array());
        } else {
            $this->request = new Bag(
                \array_merge($this->query->to_array(), $this->post->to_array())
            );
        }
    }

    /**
     * Creates and fills the query bag with $_GET parameters.
     *
     * @since 1.0.0
     */
    private function populate_query()
    {
        $this->query = new Bag($_GET);
    }

    /**
     * Creates and fills the query bag with $_POST parameters.
     *
     * @since 1.0.0
     */
    private function populate_post()
    {
        $this->post = new Bag($_POST);
    }

    /**
     * Populates Request class parameters.
     *
     * @since 1.0.0
     */
    private function populate_properties()
    {
        global $wp;

        $this->is_mobile = (bool) wp_is_mobile();
        $this->scheme = is_ssl() ? 'https' : 'http';

        $this->matched_query = $wp->matched_query;
        $this->matched_rule = $wp->matched_rule;

        $this->url = Theme_Utils::get_current_url();

        $this->path = \rtrim(\parse_url($this->url, PHP_URL_PATH), '/');
    }

    /**
     * Creates and fills the query bag with selected $_SERVER parameters.
     *
     * @since 1.0.0
     */
    private function populate_server()
    {
        $definition = [
            'REQUEST_METHOD' => [
                'filter'  => FILTER_CALLBACK,
                'options' => function ($method) {
                    return \strtoupper(\filter_var($method, FILTER_SANITIZE_STRING));
                },
            ],
            'QUERY_STRING'    => FILTER_UNSAFE_RAW,
            'REMOTE_ADDR'     => FILTER_VALIDATE_IP,
            'SERVER_PORT'     => FILTER_SANITIZE_NUMBER_INT,
            'SERVER_NAME'     => FILTER_SANITIZE_STRING,
            'HTTP_HOST'       => FILTER_SANITIZE_URL,
            'HTTP_REFERER'    => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => FILTER_SANITIZE_STRING,
        ];

        $server = \filter_input_array(INPUT_SERVER, $definition);

        if ('' !== \preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $server['HTTP_HOST'])) {
            wp_die('This site has been temporarily disabled due to suspicious activity');
        }

        $this->server = new Bag($server);
    }

    /**
     * Set a item on the request bag.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset The offset to set.
     * @param  mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->request[] = $value;
        } else {
            $this->request[ $offset ] = $value;
        }
    }

    /**
     * Whether an item exists in the request bag.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset An offset to check for.
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->request->has($offset);
    }

    /**
     * Remove an item from the request bag.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->request[ $offset ]);
    }

    /**
     * Get an item from the request bag.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset The offset to get.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->request->get($offset, null);
    }
}
