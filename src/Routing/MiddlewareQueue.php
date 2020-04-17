<?php

namespace Snap\Routing;

use RuntimeException;
use Tightenco\Collect\Support\Arr;

class MiddlewareQueue
{
    /**
     * All registered middleware.
     *
     * @var array
     */
    private static $registered_middleware = [];

    /**
     * Current middleware stack.
     *
     * @var array
     */
    private $stack = [];

    /**
     * Current group scoped stack.
     *
     * @var array
     */
    private $scoped = [];

    /**
     * Registers a middleware handler.
     *
     * @param string   $name     Name of middleware.
     * @param callable $callback Handler callback.
     */
    public static function registerMiddleware(string $name, callable $callback): void
    {
        static::$registered_middleware[$name] = $callback;
    }

    /**
     * Apply optional checks to the current route.
     *
     * These are middleware, executed one after the other allowing the
     * developer to perform additional checks against the current request.
     *
     * All passed middleware must return true to be a valid route.
     *
     * @param array|string $middleware The middleware hooks to apply to this route.
     * @param bool         $scoped     Whether to scope the middleware or not.
     */
    public function add($middleware, $scoped = false): void
    {
        $middleware = Arr::wrap($middleware);

        foreach ($middleware as $callback) {
            $parts = \explode('|', $callback);
            $args = [];

            if (\count($parts) === 2) {
                $args = \explode(',', $parts[1]);
            }

            if (!isset(static::$registered_middleware[$parts[0]])) {
                throw new RuntimeException("No middleware called '{$parts[0]}' found");
            }

            $this->stack[$parts[0]] = $args;

            if ($scoped === true) {
                $this->scoped[$parts[0]] = $args;
            }
        }
    }

    /**
     * Whether the current stack passes or not.
     *
     * @return bool
     */
    public function passes(): bool
    {
        foreach ($this->stack as $name => $args) {
            if (\call_user_func_array(static::$registered_middleware[$name], $args) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes the current group scoped middleware from the queue.
     */
    public function deScope(): void
    {
        $this->stack = array_diff_key($this->stack, $this->scoped);
        $this->scoped = [];
    }
}