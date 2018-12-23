<?php

namespace Snap\Http;

use Snap\Http\Request\File_Bag;
use ArrayAccess;
use Snap\Http\Request\Bag;
use Snap\Http\Request\Server_Bag;
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
     * @var \Snap\Http\Request\Bag
     */
    public $query = null;

    /**
     * Request post params.
     *
     * @since 1.0.0
     * @var \Snap\Http\Request\Bag
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
     * Request file params.
     *
     * @since 1.0.0
     * @var File_Bag
     */
    public $files = null;

    /**
     * Holds all available request parameters.
     *
     * POST takes precedence.
     *
     * @since 1.0.0
     * @var \Snap\Http\Request\Bag
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
        $this->server = new Server_Bag();
        $this->query = new Bag($_GET);
        $this->post = new Bag($_POST);
        $this->files = new File_Bag($_FILES);

        $this->populate_request();
        $this->populate_properties();

        $this->set_validation();
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
     * Returns a parameter from the files bag, or a default if not present.
     *
     * @since  1.0.0
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function file($key, $default = null)
    {
        return $this->files->get($key, $default);
    }

    /**
     * Set the validation error messages.
     *
     * @since  1.0.0
     * @see    https://github.com/rakit/validation#custom-validation-message for format.
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
     * @see    https://github.com/rakit/validation#available-rules for format.
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
     * @param  array $rules    Optional. Rules to use. Defaults to rules set via set_rules().
     * @param  array $messages Optional. Messages to use. Defaults to rules set via set_messages().
     */
    public function validate_ajax_request(array $rules = [], array $messages = [])
    {
        // Validation is not using $this->validation so overwrite rules and messages.
        if ($rules !== []) {
            $validation = $this->validate_data($this->request->to_array(), $rules, $messages);

            if ($validation === true) {
                \wp_send_json_success('Success');
            }

            \wp_send_json_error($validation, 400);
        } else {
            if ($this->is_valid()) {
                \wp_send_json_success($this->validation->getValidatedData());
            }

            \wp_send_json_error($this->get_errors(), 400);
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

        return !$this->validation->fails();
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
     * @param  array $inputs   The array of data to validate as key value pairs.
     * @param  array $rules    The rules to run against the data.
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
                \array_merge($this->query->to_array(), $this->post->to_array(), $this->files->to_array())
            );
        }
    }

    /**
     * Populates Request class parameters.
     *
     * @since 1.0.0
     */
    private function populate_properties()
    {
        global $wp;

        $this->is_mobile = (bool)\wp_is_mobile();
        $this->scheme = \is_ssl() ? 'https' : 'http';

        $this->matched_query = $wp->matched_query;
        $this->matched_rule = $wp->matched_rule;

        $this->url = Theme_Utils::get_current_url();

        $this->path = \rtrim(\parse_url($this->url, PHP_URL_PATH), '/');
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
            $this->request[$offset] = $value;
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
        unset($this->request[$offset]);
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

    /**
     * Get an item from the request bag.
     *
     * @since  1.0.0
     *
     * @param  mixed $name The offset to get.
     * @return mixed
     */
    public function __get($name)
    {
        return $this->request->get($name, null);
    }
}
