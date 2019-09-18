<?php

namespace Snap\Http;

use ArrayAccess;
use Snap\Http\Request\Bag;
use Snap\Http\Request\FileBag;
use Snap\Http\Request\ServerBag;
use Snap\Http\Validation\Traits\ValidatesInput;
use Snap\Http\Validation\Validator;
use Snap\Utils\Theme;

/**
 * Gathers all request variables into one place, and provides a simple API for changes affecting the response.
 */
class Request extends Validator implements ArrayAccess
{
    use ValidatesInput;

    /**
     * Request query params.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $query;

    /**
     * Request post params.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $post;

    /**
     * Request server params.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $server;

    /**
     * Cookies bag.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $cookies;

    /**
     * Request file params.
     *
     * @var \Snap\Http\Request\FileBag
     */
    public $files;

    /**
     * Holds all available request parameters.
     *
     * POST takes precedence.
     *
     * @var \Snap\Http\Request\Bag
     */
    public $input;

    /**
     * WordPress query vars.
     *
     * @var \Snap\Http\Request\Bag
     */
    protected static $wp;

    /**
     * The current query being run by WordPress.
     *
     * @var string
     */
    protected $matched_query;

    /**
     * The current rewrite rule being run.
     *
     * @var string
     */
    protected $matched_rule;

    /**
     * Whether WordPress thinks the current request is from a mobile.
     *
     * @var boolean
     */
    protected $is_mobile = false;

    /**
     * The client IP address.
     *
     * @var string|null
     */
    protected $clientIp = null;

    /**
     * The current request URL.
     *
     * @var string
     */
    protected $url;

    /**
     * The current request host (domain).
     *
     * @var string
     */
    protected $host;

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
        $this->server = new ServerBag();
        $this->cookies = new Bag($_COOKIE);
        $this->populateInput();
        $this->populateProperties();

        // Set up the Validation instance.
        parent::__construct($_GET + $_POST + $_FILES, $this->rules(), $this->messages());

        // Set blank global ErrorBag.
        static::$globalErrors = $this->validation->errors();

        if (!empty($this->aliases())) {
            $this->setAliases($this->aliases());
        }
    }

    /**
     * Get an item from the request bag.
     *
     * @param  mixed $name The offset to get.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function __get($name)
    {
        if ($name === 'wp') {
            return static::$wp;
        }
        return $this->input->get($name, null) ?? $this->files->get($name, null);
    }

    /**
     * Check if an input element is set on the request.
     *
     * @param  mixed $name The offset to get.
     * @return bool
     */
    public function __isset($name)
    {
        return ! \is_null($this->__get($name));
    }

    /**
     * Get the request HTTP method.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->server('REQUEST_METHOD');
    }

    /**
     * Returns the current URL.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Returns the current URL path.
     *
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * Returns the current URL host (Domain).
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Returns the current URL path.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Attempts to return a truthful client IP.
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        // Bail early if we already have a match.
        if ($this->clientIp) {
            return $this->clientIp;
        }

        $search = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        foreach ($search as $key) {
            if ($this->server->has($key) === true) {
                $ips = \explode(',', $this->server->getRaw($key));

                // There was only one IP.
                if (\count($ips) === 1) {
                    $this->clientIp = $ips[0];
                }

                foreach ($ips as $ip) {
                    if (\filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false) {
                        $this->clientIp = $ip;
                    }
                }
            }
        }

        return $this->clientIp;
    }

    /**
     * Returns the path segments.
     *
     * @return array
     */
    public function getPathSegments(): array
    {
        return \array_values(\array_filter(\explode('/', $this->path)));
    }

    /**
     * Get the current query being used by WordPress.
     *
     * @return string
     */
    public function getMatchedQuery(): string
    {
        return $this->matched_query;
    }

    /**
     * Get the current rewrite rule used by WordPress.
     *
     * @return string
     */
    public function getMatchedRule(): string
    {
        return $this->matched_rule;
    }

    /**
     * Whether the current request is from a mobile browser.
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        return $this->is_mobile;
    }

    /**
     * Checks if the current request matches the supplied HTTP method.
     *
     * @param  string $method HTTP method to check against. Case insensitive.
     * @return boolean
     */
    public function isMethod($method): bool
    {
        return \strtoupper($method) === $this->getMethod();
    }

    /**
     * Returns a parameter from the request bag, or a default if not present.
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function input($key = null, $default = null)
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
    public function server($key = null, $default = null)
    {
        return $this->server->get($key, $default);
    }

    /**
     * Returns a parameter from the cookie bag, or a default if not present.
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function cookie($key = null, $default = null)
    {
        return $this->cookies->get($key, $default);
    }

    /**
     * Returns a parameter from the post bag, or a default if not present.
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed
     */
    public function post($key = null, $default = null)
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
    public function query($key = null, $default = null)
    {
        return $this->query->get($key, $default);
    }

    /**
     * Returns a parameter from the files bag, or a default if not present.
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function files($key = null, $default = null)
    {
        return $this->files->get($key, $default);
    }

    /**
     * Returns a parameter from the WordPress query vars bag, or a default if not present.
     *
     * @param  string $key     The parameter key to look for.
     * @param  mixed  $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function wp($key = null, $default = null)
    {
        return static::$wp->get($key, $default);
    }

    /**
     * Check if the $key exists within the post or query bags.
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function has($key): bool
    {
        return $this->post->has($key) ?: $this->query->has($key) ?: false;
    }

    /**
     * Check if the $key exists within the file bag.
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function hasFile($key): bool
    {
        return $this->files->has($key);
    }

    /**
     * Whether there is any user submitted data.
     *
     * @return bool
     */
    public function hasInput(): bool
    {
        return !$this->input->isEmpty() || !$this->files->isEmpty();
    }

    /**
     * Determine if an input is present and not empty within the query or post bags.
     *
     * @param array $keys The keys to test.
     * @return bool
     */
    public function filled(...$keys): bool
    {
        foreach ($keys as $key) {
            if ($this->hasFile($key) === true) {
                return true;
            }

            if ($this->has($key) === false || $this->input($key) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the current request is the provided post template.
     *
     * @param string $post_template The template to check for.
     * @return bool
     */
    public function isPostTemplate($post_template): bool
    {
        return \is_page_template(Theme::getPostTemplatesPath($post_template));
    }

    /**
     * Detect whether the current request is to the login page.
     *
     * @return bool
     */
    public function isLoginPage()
    {
        $abs_path = \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $files = \get_included_files();

        if (\in_array($abs_path . 'wp-login.php', $files) || \in_array($abs_path . 'wp-register.php', $files)
            || isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php'
            || isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] == '/wp-login.php'
        ) {
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
            $this->input[$offset] = $value;
        }
    }

    /**
     * Whether an item exists in the request bag.
     *
     * @param  mixed $offset An offset to check for.
     * @return boolean
     */
    public function offsetExists($offset): bool
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
        unset($this->input[$offset]);
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
     * Populates params from the global $wp object.
     */
    public function populateWpParams()
    {
        global $wp;

        $this->matched_query = $wp->matched_query;
        $this->matched_rule = $wp->matched_rule;
        static::$wp = new Bag($wp->query_vars + $wp->extra_query_vars);
    }

    /**
     * Populates Request class parameters.
     */
    private function populateProperties()
    {
        $this->is_mobile = (bool)\wp_is_mobile();
        $this->scheme = \is_ssl() ? 'https' : 'http';
        $this->url = Theme::getCurrentUrl();
        $this->host = \parse_url($this->getUrl(), PHP_URL_HOST);
        $this->path = \rtrim(\parse_url($this->url, PHP_URL_PATH), '/');
    }

    /**
     * Creates and fills the request bag with query params, and $_POST params if present.
     *
     * Post parameters take precedence and overwrite query params of the same key.
     */
    private function populateInput()
    {
        $this->query = new Bag($_GET);
        $this->post = new Bag($_POST);
        $this->files = new FileBag($_FILES);

        if ($this->isMethod('GET')) {
            $this->input = new Bag($_GET);
        } else {
            $this->input = new Bag(
                \array_merge($_GET, $_POST)
            );
        }
    }
}
