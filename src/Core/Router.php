<?php

namespace Snap\Core;

use Exception;
use BadMethodCallException;
use closure;

/**
 * A wrapper which replaces the standard if/else/switch block, and provides a more fluent API for
 * the front controllers.
 *
 * @since 1.0.0
 */
class Router
{
    /**
     * Whether the current route is short-circuited.
     *
     * If set to true, then all remaining methods on this route are skipped.
     *
     * @since 1.0.0
     * @var boolean
     */
    private $shortcircuit = false;
    
    /**
     * Whether the current request has matched a route or not.
     *
     * @since 1.0.0
     * @var boolean
     */
    private $has_matched_route = false;

    /**
     * Middleware stack to apply to the current route.
     *
     * @since 1.0.0
     * @var array
     */
    private $middleware = [];

    /**
     * A record of the current group level's middleware.
     *
     * @since 1.0.0
     * @var array
     */
    private $last_middleware = [];

    /**
     * Whether the active route is within a group or not.
     *
     * @since 1.0.0
     * @var boolean
     */
    private $is_group = false;

    /**
     * Groups routes with the current middleware stack.
     *
     * Useful when you want to apply the same middleware to similar routes,
     * such as an area restricted by user role.
     *
     * @since 1.0.0
     *
     * @param  closure $callback A closure in which the grouped routes are registered.
     * @return Router
     */
    public function group(closure $callback)
    {
        if ($this->can_proceed()) {
            // If this is a top level group.
            if ($this->is_group === false) {
                $this->is_group = true;

                $callback();

                $this->is_group = false;
                $this->shortcircuit = false;
            } else {
                $callback();
                // As this is a nested group, remove this level's middleware.
                $this->middleware = \array_diff($this->middleware, $this->last_middleware);

                $this->last_middleware = [];
            }
        }

        return $this;
    }

    /**
     * Check if a custom expression returns true.
     *
     * @since 1.0.0
     *
     * @param  bool|callable $result The result of a custom expression.
     * @return Router
     */
    public function is($result)
    {
        if (\is_callable($result)) {
            $result = $result();
        }

        if ($this->can_proceed() && $result !== true) {
            $this->shortcircuit = true;
        }

        return $this;
    }

    /**
     * Checks that a custom expression does not return true.
     *
     * @since 1.0.0
     *
     * @param  bool $result The result of a custom expression.
     * @return Router
     */
    public function is_not($result)
    {
        if (\is_callable($result)) {
            $result = $result();
        }

        if ($this->can_proceed() && $result === true) {
            $this->shortcircuit = true;
        }

        return $this;
    }

    /**
     * Wrapper for is_page_template so it can be used when defining a route.
     *
     * @since  1.0.0
     *
     * @param  string $template Optional specific template to check for.
     * @return Router
     */
    public function is_page_template($template = '')
    {
        if ($this->can_proceed() && is_page_template($template) === false) {
            $this->shortcircuit = true;
        }

        return $this;
    }

    /**
     * Apply optional checks to the current route.
     *
     * These are middleware, executed one after the other allowing the
     * developer to perform additional checks against the current request.
     *
     * All passed middleware must return true to be a valid route.
     *
     * @since 1.0.0
     *
     * @param  array|string $stack The middleware hooks to apply to this route.
     * @return Router
     */
    public function using($stack = [])
    {
        if (! \is_array($stack)) {
            $stack = [ $stack ];
        }

        foreach ($stack as $callback) {
            $parts = \explode('|', $callback);
            $args = [];

            if (\count($parts) === 2) {
                $args = \explode(',', $parts[1]);
            }

            $action = $this->get_middleware_action($parts[0]);

            $this->middleware[ $action ] = $args;

            // If we are in a nested group, save this level's stack for later removal.
            if ($this->is_group === true) {
                $this->last_middleware[ $action ] = $args;
            }
        }

        return $this;
    }

    /**
     * Returns the hook name for a given middleware.
     *
     * @since  1.0.0
     *
     * @throws BadMethodCallException If the hook was not found.
     *
     * @param  string $middleware_name The name of the middleware.
     * @return string
     */
    private function get_middleware_action($middleware_name)
    {
        if (has_action("snap_middleware_{$middleware_name}")) {
            return "snap_middleware_{$middleware_name}";
        } else {
            throw new BadMethodCallException("No middleware called '{$middleware_name}' found");
        }
    }

    /**
     * Resets internal pointers.
     *
     * @since  1.0.0
     *
     * @return Router
     */
    public function reset()
    {
        if ($this->has_matched_route === false) {
            $this->shortcircuit = false;
        }

        // Only reset middleware stack if this is not in a group callback.
        if ($this->is_group === false) {
            $this->middleware = [];
        }

        return $this;
    }

    /**
     * Render a view and stop any other routes from processing.
     *
     * Views (like template parts) are loaded as {name}{-slug}.php.
     * If a slug cannot be found, then {view}.php is loaded instead.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug of the view to render.
     */
    public function view($slug)
    {
        if ($this->can_proceed()) {
            // As this is the correct route, apply middleware stack.
            $this->apply_middleware();

            // Passed all middleware.
            if ($this->can_proceed()) {
                do_action("snap_render_view_{$slug}", Snap::request());
                
                Snap::view()->render($slug);

                $this->has_matched_route = true;

                \ob_end_flush();
            }
        }
    }

    /**
     * Dispatch a controller action.
     *
     * The dispatched action and the controller class are auto-wired.
     *
     * @since  1.0.0
     *
     * @throws Exception If the supplied controller doesn't exist.
     *
     * @param  string $controller The controller name followed by the action, separated by an @.
     *                            eg. "MyController@MyAction"
     *                            If no action is supplied, then 'index' is presumed.
     */
    public function dispatch($controller)
    {
        if ($this->can_proceed()) {
            // As this is the correct route, apply middleware stack.
            $this->apply_middleware();

            // Passed all middleware.
            if ($this->can_proceed()) {
                list($class, $action) = \explode('@', $controller);

                if ($action === null) {
                    $action = 'index';
                }

                $fqn = '\\Theme\\Controllers\\' . $class;

                if (\class_exists($fqn)) {
                    Snap::services()->resolveMethod(
                        Snap::services()->resolve($fqn),
                        $action
                    );
                } else {
                    throw new Exception("The controller {$fqn} could not be found.");
                }

                $this->has_matched_route = true;

                \ob_end_flush();
            }
        }
    }

    /**
     * Magic method to wrap any undefined routes to the global WP_Query.
     *
     * @since  1.0.0
     *
     * @param  string $name      The called method.
     * @param  mixed  $arguments The called method arguments.
     * @return Router
     */
    public function __call($name, $arguments)
    {
        global $wp_query;

        // WP_Query also uses call, which can lead to unwanted negatives.
        if (\is_callable([ $wp_query, $name ]) && \method_exists($wp_query, $name)) {
            if ($this->can_proceed() && $wp_query->{$name}($arguments) === false) {
                $this->shortcircuit = true;
            }

            return $this;
        }

        if (\function_exists($name)) {
            if ($this->can_proceed() && $name($arguments) !== true) {
                $this->shortcircuit = true;
            }

            return $this;
        }

        return $this;
    }

    /**
     * Apply the current stack of middleware to the current route.
     *
     * @since 1.0.0
     */
    private function apply_middleware()
    {
        if (empty($this->middleware)) {
            return;
        }

        foreach ($this->middleware as $hook => $args) {
            /**
             * Execute the middleware callback via snap_middleware_{$middleware} filter.
             *
             * This hook can return true to continue processing the route, bail via a redirect,
             * or return false to short circuit and move to the next route.
             *
             * @since  1.0.0
             *
             * @param Request $request The current request for quick access.
             * @return  bool Whether to continue processing this route.
             */
            if (\apply_filters($hook, Snap::request(), ...$args) !== true) {
                $this->shortcircuit = true;
            }
        }
    }

    /**
     * Test to see if the current route should continue to be processed.
     *
     * @since  1.0.0
     *
     * @return bool
     */
    private function can_proceed()
    {
        return $this->shortcircuit === false && $this->has_matched_route === false;
    }
}
