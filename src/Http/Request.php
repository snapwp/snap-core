<?php

namespace Snap\Http;

use ArrayAccess;
use Snap\Http\Request\Bag;
use Snap\Http\Request\File_Bag;
use Snap\Http\Request\Server_Bag;
use Snap\Http\Validation\Traits\Validates_Input;
use Snap\Utils\Theme_Utils;

/**
 * Gathers all request variables into one place, and provides a simple API for changes affecting the response.
 */
class Request implements ArrayAccess
{
    use Validates_Input;

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
    public $input = null;

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

        $this->populate_input();
        $this->populate_properties();
        $this->setup_validation($_GET + $_POST + $_FILES);
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
        return $this->input->get($key, $default);
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
     * Check if the $key exists within the post or query bags.
     *
     * @since 1.0.0
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function has($key)
    {
        return $this->post->has($key) ?: $this->query->has($key) ?: false;
    }

    /**
     * Check if the $key exists within the file bag.
     *
     * @since 1.0.0
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function has_file($key)
    {
        return $this->files->has($key);
    }

    /**
     * Determine if an input is present and not empty within the query or post bags.
     *
     * @since 1.0.0
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function filled($key)
    {
        return $this->has($key) && !empty($this->get($key));
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
            $this->input[] = $value;
        } else {
            $this->input[ $offset ] = $value;
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
        return $this->input->has($offset);
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
        unset($this->input[ $offset ]);
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
        return $this->input->get($offset, null);
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
        return $this->input->get($name, null);
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
     * Creates and fills the request bag with query params, and $_POST params if present.
     *
     * Post parameters take precedence and overwrite query params of the same key.
     *
     * @since  1.0.0
     */
    private function populate_input()
    {
        if ($this->get_method() === 'GET') {
            $this->input = new Bag($this->query->to_array());
        } else {
            $this->input = new Bag(
                \array_merge($this->query->to_array(), $this->post->to_array(), $this->files->to_array())
            );
        }
    }
}
