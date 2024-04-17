<?php

namespace Snap\Http;

use ArrayAccess;
use Snap\Http\Request\Bag;
use Snap\Http\Request\FileBag;
use Snap\Http\Request\ServerBag;
use Snap\Http\Validation\Traits\ValidatesInput;
use Snap\Http\Validation\Validator;
use Snap\Routing\UrlRoute;
use Snap\Utils\Theme;
use Somnambulist\Components\Validation\ErrorBag;

/**
 * Gathers all request variables into one place, and provides a simple API for changes affecting the response.
 */
class Request extends Validator implements ArrayAccess
{
    use ValidatesInput;

    /**
     * Holds the global ErrorBag instance for use in templates.
     */
    protected static ErrorBag $globalErrors;

    /**
     * Returns the ErrorBag being used by the current request.
     */
    public static function getGlobalErrors(): ErrorBag
    {
        return static::$globalErrors;
    }

    /**
     * Request query params.
     */
    public Bag $query;

    /**
     * Request post params.
     */
    public Bag $post;

    /**
     * Request server params.

     */
    public ServerBag $server;

    /**
     * Cookies bag.
     */
    public Bag $cookies;

    /**
     * Request file params.
     */
    public FileBag $files;

    /**
     * Holds all available request parameters.
     *
     * POST takes precedence.
     */
    public Bag $input;

    /**
     * Route parameters.
     */
    public Bag $route;

    /**
     * WordPress query vars.
     */
    protected static Bag $wp;

    /**
     * The current query being run by WordPress.
     *
     * @var string
     */
    protected string $matched_query;

    /**
     * The current rewrite rule being run.
     */
    protected string $matched_rule;

    /**
     * Whether WordPress thinks the current request is from a mobile.
     */
    protected bool $is_mobile = false;

    /**
     * The client IP address.
     */
    protected ?string $clientIp;

    /**
     * The current request URL.
     */
    protected string $url;

    /**
     * The current request host (domain).
     */
    protected string $host;

    /**
     * The current request path.
     */
    protected string $path;

    /**
     * The current request scheme.
     */
    protected string $scheme;

    /**
     * Populate request variables and properties.
     */
    public function __construct()
    {
        $this->server = new ServerBag();
        $this->cookies = new Bag($_COOKIE);
        $this->populateInput();
        $this->populateProperties();

        if (!empty($this->rules())) {
            $this->make($_GET + $_POST + $_FILES, $this->rules(), $this->messages());
        }

        // Set blank global ErrorBag.
        static::$globalErrors = new ErrorBag([]);

        if (!empty($this->aliases())) {
            $this->setAliases($this->aliases());
        }
    }

    /**
     * Get an item from the request bag.
     *
     * @param mixed $name The offset to get.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function __get($name)
    {
        if ($name === 'wp') {
            return static::$wp;
        }
        return $this->input->get($name) ?? $this->files->get($name);
    }

    /**
     * Check if an input element is set on the request.
     *
     * @param mixed $name The offset to get.
     * @return bool
     */
    public function __isset($name)
    {
        return $this->__get($name) !== null;
    }

    /*
     * *****************************************************************************************************************
     * Validation Methods
     * *****************************************************************************************************************
     */

    public function validate(array $rules = [], array $messages = []): static
    {
        $this->make($_GET + $_POST + $_FILES, $rules, $messages);
        return $this;
    }


    /**
     * Validates the request using the rules and messages set on the internal validation instance.
     *
     * @return boolean If the validation passed or not.
     */
    public function isValid(): bool
    {
        $this->validation->validate();
        static::$globalErrors = $this->validation->errors();
        return !$this->validation->fails();
    }

    /*
     * *****************************************************************************************************************
     * Request Information Methods
     * *****************************************************************************************************************
     */

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
     * @param string $method HTTP method to check against. Case insensitive.
     * @return boolean
     */
    public function isMethod(string $method): bool
    {
        return \strtoupper($method) === $this->getMethod();
    }

    /*
     * *****************************************************************************************************************
     * Input Methods
     * *****************************************************************************************************************
     */

    /**
     * Returns a parameter from the request bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return mixed|\Snap\Http\Request\Bag|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }
        return $this->input->get($key, $default);
    }

    /**
     * Returns a parameter from the server bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server->get($key, $default);
    }

    /**
     * Returns a parameter from the cookie bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function cookie(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies->get($key, $default);
    }

    /**
     * Returns a parameter from the post bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post->get($key, $default);
    }

    /**
     * Returns a parameter from the query bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query->get($key, $default);
    }

    /**
     * Returns a parameter from the files bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\FileBag|mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function files(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files->get($key, $default);
    }

    /**
     * Returns a parameter from the WordPress query vars bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function wp(string $key = null, $default = null)
    {
        if ($key === null) {
            return static::$wp;
        }
        return static::$wp->get($key, $default);
    }

    /**
     * Returns a parameter from the WordPress query vars bag, or a default if not present.
     *
     * @param string|null $key     The parameter key to look for.
     * @param mixed       $default A default value to return if not present.
     * @return \Snap\Http\Request\Bag|mixed
     */
    public function route(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->route;
        }
        return $this->route->get($key, $default);
    }

    /**
     * Check if the $key exists within the post or query bags.
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->post->has($key) ?: $this->query->has($key) ?: false;
    }

    /**
     * Check if the $key exists within the file bag.
     *
     * @param string $key The key to check for.
     * @return bool
     */
    public function hasFile(string $key): bool
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
    public function isPostTemplate(string $post_template): bool
    {
        return \is_page_template(Theme::getPostTemplatePath($post_template));
    }

    /**
     * Detect whether the current request is to the login page.
     *
     * @return bool
     */
    public function isLoginPage(): bool
    {
        $abs_path = \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $files = \get_included_files();

        if ((isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php')
            || (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] === '/wp-login.php')
            || \in_array($abs_path . 'wp-login.php', $files) || \in_array($abs_path . 'wp-register.php', $files)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set an item on the request bag.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->input[] = $value;
        } else {
            $this->input[$offset] = $value;
        }
    }

    /**
     * Whether an item exists in the request bag.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->input->has($offset);
    }

    /**
     * Remove an item from the request bag.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->input[$offset]);
    }

    /**
     * Get an item from the request bag.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->input->get($offset, null);
    }

    /**
     * Populates params from the global $wp object.
     */
    public function populateWpParams(): void
    {
        global $wp;

        $this->matched_query = $wp->matched_query;
        $this->matched_rule = $wp->matched_rule;
        static::$wp = new Bag($wp->query_vars + $wp->extra_query_vars);
    }

    /**
     * Set the current matched static route.
     *
     * @param UrlRoute $route
     */
    public function setCurrentRoute(UrlRoute $route): void
    {
        $this->route = new Bag($route->parameters());
    }

    /**
     * Populates Request class parameters.
     */
    private function populateProperties(): void
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
    private function populateInput(): void
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
