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
     * @var \Snap\Http\Request\Bag
     */
    public $query = null;

    /**
     * Request post params.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $post = null;

    /**
     * Request server params.
     *
     * @var Bag
     */
    public $server = null;

    /**
     * Request file params.
     *
     * @var File_Bag
     */
    public $files = null;

    /**
     * Holds all available request parameters.
     *
     * POST takes precedence.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $input = null;

    /**
     * The current query being run by WordPress.
     *
     * @var string
     */
    public $matched_query;

    /**
     * The current rewrite rule being run.
     *
     * @var string
     */
    public $matched_rule;

    /**
     * Whether WordPress thinks the current request is from a mobile.
     *
     * @var boolean
     */
    public $is_mobile = false;

    /**
     * The current request URL.
     *
     * @var string
     */
    protected $url;

    /**
     * The current request path.
     *
     * @var string
     */
    protected $path;

    /**
     * The current request scheme.
     *
     * @var string
     */
    protected $scheme;

    /**
     * Populate request variables and properties.
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
     * Get an item from the request bag.
     *
     * @param  mixed $name The offset to get.
     * @return mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[]
     */
    public function __get($name)
    {
        return $this->input->get($name, null);
    }

    /**
     * Get the request HTTP method.
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
     * @return string
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     * Returns the current URL path.
     *
     * @return string
     */
    public function get_scheme()
    {
        return $this->scheme;
    }

    /**
     * Returns the current URL host (Domain).
     *
     * @return string
     */
    public function get_host()
    {
        return $this->server('SERVER_NAME');
    }

    /**
     * Returns the current URL path.
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
     * @return array
     */
    public function get_path_segments()
    {
        return \array_values(\array_filter(\explode('/', $this->path)));
    }

    /**
     * Checks if the current request matches the supplied HTTP method.
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
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[]
     */
    public function get($key, $default = null)
    {
        return $this->input->get($key, $default);
    }

    /**
     * Returns a parameter from the server bag, or a default if not present.
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
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[]
     */
    public function files($key, $default = null)
    {
        return $this->files->get($key, $default);
    }

    /**
     * Check if the $key exists within the post or query bags.
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
     * @param string $key The key to check for.
     * @return bool
     */
    public function filled($key)
    {
        return $this->has($key) && !empty($this->get($key));
    }

    /**
     * Whether the current request is the provided post template.
     *
     * @param string $post_template The template to check for.
     * @return bool
     */
    public function is_post_template($post_template): bool
    {
        return \is_page_template(Theme_Utils::get_post_templates_path($post_template));
    }

    /**
     * Detect whether the current request is to the login page.
     *
     * @return bool
     */
    public function is_wp_login()
    {
        $abs_path = \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ABSPATH);

        $files = \get_included_files();

        if (\in_array($abs_path . 'wp-login.php', $files) || \in_array($abs_path . 'wp-register.php', $files)) {
            return true;
        }

        if (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') {
            return true;
        }

        if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] == '/wp-login.php') {
            return true;
        }

        return false;
    }

    /**
     * Set a item on the request bag.
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
     * @param  mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->input[ $offset ]);
    }

    /**
     * Get an item from the request bag.
     *
     * @param  mixed $offset The offset to get.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->input->get($offset, null);
    }

    /**
     * Populates Request class parameters.
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
