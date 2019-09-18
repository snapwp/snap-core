<?php

namespace Snap\Core;

use BadMethodCallException;
use closure;
use Exception;
use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Services\View;

/**
 * A wrapper which replaces the standard if/else/switch block, and provides a more fluent API for
 * the front controllers.
 */
class Router
{
    /**
     * Whether the current route is short-circuited.
     *
     * If set to true, then all remaining methods on this route are skipped.
     *
     * @var boolean
     */
    private $shortcircuit = false;

    /**
     * Whether the current request has matched a route or not.
     *
     * @var boolean
     */
    private $has_matched_route = false;

    /**
     * Middleware stack to apply to the current route.
     *
     * @var array
     */
    private $middleware = [];

    /**
     * A record of the current group level's middleware.
     *
     * @var array
     */
    private $last_middleware = [];

    /**
     * Whether the active route is within a group or not.
     *
     * @var boolean
     */
    private $is_group = false;

    /**
     * Groups routes with the current middleware stack.
     *
     * Useful when you want to apply the same middleware to similar routes,
     * such as an area restricted by user role.
     *
     * @param  closure $callback A closure in which the grouped routes are registered.
     * @return $this
     */
    public function group(closure $callback)
    {
        if ($this->canProceed()) {
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
     * @param  bool|callable $result The result of a custom expression.
     * @return $this
     */
    public function when($result)
    {
        if (\is_callable($result)) {
            $result = $result();
        }

        if ($this->canProceed() && $result !== true) {
            $this->shortcircuit = true;
        }

        return $this;
    }

    /**
     * Checks that a custom expression does not return true.
     *
     * @param  bool $result The result of a custom expression.
     * @return $this
     */
    public function not($result)
    {
        if (\is_callable($result)) {
            $result = $result();
        }

        if ($this->canProceed() && $result === true) {
            $this->shortcircuit = true;
        }

        return $this;
    }

    /**
     * Checks that a custom expression does not return true.
     *
     * @param $template
     * @return $this
     */
    public function whenPostTemplate($template)
    {
        if ($this->canProceed() && Request::isPostTemplate($template) === false) {
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
     * @param  array|string $middleware The middleware hooks to apply to this route.
     * @return $this
     */
    public function using($middleware = [])
    {
        if (!\is_array($middleware)) {
            $middleware = [$middleware];
        }

        foreach ($middleware as $callback) {
            $parts = \explode('|', $callback);
            $args = [];

            if (\count($parts) === 2) {
                $args = \explode(',', $parts[1]);
            }

            $action = $this->getMiddlewareAction($parts[0]);

            $this->middleware[$action] = $args;

            // If we are in a nested group, save this level's stack for later removal.
            if ($this->is_group === true) {
                $this->last_middleware[$action] = $args;
            }
        }

        return $this;
    }

    /**
     * Render a view and stop any other routes from processing.
     *
     * @param string $slug The slug of the view to render.
     * @param array  $data Array of data to pass to the view.
     */
    public function view($slug, $data = [])
    {
        if ($this->canProceed()) {
            // As this is the correct route, apply middleware stack.
            $this->applyMiddleware();

            // Passed all middleware.
            if ($this->canProceed()) {
                \do_action("snap_render_view_{$slug}", Container::get('request'), $data);

                View::render($slug, $data);

                $this->has_matched_route = true;

                echo \ob_get_clean();
            }
        }
    }

    /**
     * Dispatch a controller action.
     *
     * The dispatched action and the controller class are auto-wired.
     *
     * @param  string $controller The controller name followed by the action, separated by an @.
     *                            eg. "My_Controller@MyAction"
     *                            If no action is supplied, then 'index' is presumed.
     * @throws Exception If the supplied controller doesn't exist.
     */
    public function dispatch($controller)
    {
        if ($this->canProceed()) {
            // As this is the correct route, apply middleware stack.
            $this->applyMiddleware();

            // Passed all middleware.
            if ($this->canProceed()) {
                list($class, $action) = \explode('@', $controller);

                if ($action === null) {
                    $action = 'index';
                }

                $fqn = '\\Theme\\Http\\Controllers\\' . $class;

                if (\class_exists($fqn)) {
                    Container::resolveMethod(Container::resolve($fqn), $action);
                } else {
                    throw new Exception("The controller {$fqn} could not be found.");
                }

                $this->has_matched_route = true;

                echo \ob_get_clean();
            }
        }
    }

    /**
     * Resets internal pointers.
     *
     * @return $this
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
     * Returns the hook name for a given middleware.
     *
     * @throws BadMethodCallException If the hook was not found.
     *
     * @param  string $middleware_name The name of the middleware.
     * @return string
     */
    private function getMiddlewareAction($middleware_name): string
    {
        if (\has_action("snap_middleware_{$middleware_name}")) {
            return "snap_middleware_{$middleware_name}";
        } else {
            throw new BadMethodCallException("No middleware called '{$middleware_name}' found");
        }
    }

    /**
     * Apply the current stack of middleware to the current route.
     */
    private function applyMiddleware()
    {
        if (empty($this->middleware)) {
            return;
        }

        foreach ($this->middleware as $hook => $args) {
            $args = empty($args) ? [null] : $args;
            
            /**
             * Execute the middleware callback via snap_middleware_{$middleware} filter.
             *
             * This hook can return true to continue processing the route, bail via a redirect,
             * or return false to short circuit and move to the next route.
             *
             * @since  1.0.0
             *
             * @return  bool Whether to continue processing this route.
             */
            if (\apply_filters($hook, ...$args) !== true) {
                $this->shortcircuit = true;
            }
        }
    }

    /**
     * Test to see if the current route should continue to be processed.
     *
     * @return bool
     */
    private function canProceed(): bool
    {
        return $this->shortcircuit === false && $this->has_matched_route === false;
    }
}
