<?php

namespace Snap\Routing;

use Closure;
use Exception;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Services\Response;
use Snap\Services\View;

class Router
{
    /**
     * The current matched UrlRoute (if any).
     *
     * @var \Snap\Routing\UrlRoute
     */
    private $current_route;

    /**
     * Whether the current route can proceed processing.
     *
     * @var bool
     */
    private $can_proceed = true;

    /**
     * Whether the current request has matched a route or not.
     *
     * @var boolean
     */
    private $has_matched_route = false;

    /**
     * Whether the active route is within a group or not.
     *
     * @var boolean
     */
    private $is_group = false;

    /**
     * Holds active route's allowed methods.
     *
     * @var null|array
     */
    private $methods;

    /**
     * The current controller namespace.
     *
     * @var string
     */
    private $namespace = '\\Theme\\Http\\Controllers\\';

    /**
     * Middleware queue for current route/group.
     *
     * @var \Snap\Routing\MiddlewareQueue
     */
    private $middleware_queue;

    /**
     * Groups routes with the current middleware stack.
     *
     * Useful when you want to apply the same middleware to similar routes,
     * such as an area restricted by user role.
     *
     * @param closure $callback A closure in which the grouped routes are registered.
     * @return $this
     */
    public function group(closure $callback): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        // If this is a top level group.
        if ($this->is_group === false) {
            $this->is_group = true;
            $callback();
            $this->is_group = false;
        } else {
            $callback();
        }

        return $this;
    }

    /**
     * Check if a custom expression returns true.
     *
     * @param bool|callable $result The result of a custom expression.
     * @return $this
     */
    public function when($result): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        if (\is_callable($result)) {
            $result = $result();
        }

        if ($result !== true) {
            $this->stopProcessing();
        }

        return $this;
    }

    /**
     * Checks that a custom expression does not return true.
     *
     * @param bool|callable $result The result of a custom expression.
     * @return $this
     */
    public function not($result): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        if (\is_callable($result)) {
            $result = $result();
        }

        if ($result === true) {
            $this->stopProcessing();
        }

        return $this;
    }

    /**
     * Indicate the current route should match against a URL pattern.
     *
     * @param string $url URL pattern.
     * @return $this
     */
    public function url(string $url): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        $this->makeUrlRoute();
        $this->current_route->addUrl($url);

        if ($this->current_route->isMatch() === false) {
            $this->stopProcessing();
        }

        return $this;
    }

    /**
     * Provide additional regex rules URL params have to pass to be considered valid.
     *
     * @param array $map Array of URL param name => regex rule.
     * @return $this
     */
    public function where(array $map): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        $this->makeUrlRoute();
        $this->current_route->addTests($map);

        if ($this->current_route->isMatch() === false) {
            $this->stopProcessing();
        }

        return $this;
    }

    /**
     * Checks that a custom expression does not return true.
     *
     * @param null|string $template Optional. Specific template slug to match against.
     * @return $this
     */
    public function whenPostTemplate(string $template = null): Router
    {
        if ($this->canProceed() === false) {
            return $this;
        }

        if ($template === null) {
            return $this->when(\is_page_template());
        }

        if (Request::isPostTemplate($template) === false) {
            $this->stopProcessing();
        }

        return $this;
    }

    /**
     * Shorthand for dispatching the current post template for a matched route.
     */
    public function dispatchPostTemplate(): void
    {
        $this->view(\get_page_template_slug());
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
     * @return $this
     */
    public function using($middleware = []): Router
    {
        if ($this->middleware_queue === null) {
            $this->middleware_queue = Container::get(MiddlewareQueue::class);
        }

        $this->middleware_queue->add($middleware, $this->is_group);

        return $this;
    }

    /**
     * Render a view and stop any other routes from processing.
     *
     * @param string $view The slug of the view to render.
     * @param array  $data Array of data to pass to the view.
     */
    public function view(string $view, $data = []): void
    {
        $this->checkMethod();
        $this->maybeRunMiddleware();

        // Passed all middleware.
        if ($this->canProceed() === false) {
            return;
        }

        $this->matchRoute();

        $view = \str_replace(Config::get('theme.templates_directory') . '/views/', '', $view);
        View::render($view, $data);
    }

    /**
     * Dispatch a controller action.
     *
     * The dispatched action and the controller class are auto-wired.
     *
     * @param string $controller  The controller name followed by the action, separated by an @.
     *                            eg. "My_Controller@MyAction"
     *                            If no action is supplied, then 'index' is presumed.
     * @throws Exception If the supplied controller doesn't exist.
     */
    public function dispatch(string|array $controller): void
    {
        $this->checkMethod();
        $this->maybeRunMiddleware();

        if ($this->canProceed() === false) {
            return;
        }

        if (is_array($controller)) {
            [$fqn, $action] = $controller;
        } else {
            [$class, $action] = \explode('@', $controller);

            $fqn = $this->namespace . $class;
        }

        if (\class_exists($fqn)) {
            $this->matchRoute();
            Container::resolveMethod(Container::resolve($fqn), $action);
            return;
        }

        throw new Exception("The controller {$fqn} could not be found.");
    }

    /**
     * Set the current namespace to use when resolving controllers.
     *
     * @param string $namespace New namespace.
     * @return \Snap\Routing\Router
     */
    public function namespace(string $namespace): Router
    {
        if (\strpos($namespace, '\\') === 0) {
            $this->namespace = $namespace;
        } else {
            $this->namespace .= \trim($namespace, '\\') . "\\";
        }

        return $this;
    }

    /**
     * Indicates the current route should only match against the GET HTTP verb.
     *
     * @return $this
     */
    public function get(): Router
    {
        $this->methods = ['GET'];
        $this->checkMethod();
        return $this;
    }

    /**
     * Indicates the current route should only match against the POST HTTP verb.
     *
     * @return $this
     */
    public function post(): Router
    {
        $this->methods = ['POST'];
        $this->checkMethod();
        return $this;
    }

    /**
     * Indicates the current route should only match against the PUT HTTP verb.
     *
     * @return $this
     */
    public function put(): Router
    {
        $this->methods = ['PUT'];
        $this->checkMethod();
        return $this;
    }

    /**
     * Indicates the current route should only match against the PATCH HTTP verb.
     *
     * @return $this
     */
    public function patch(): Router
    {
        $this->methods = ['PATCH'];
        $this->checkMethod();
        return $this;
    }

    /**
     * Indicates the current route should only match against the DELETE HTTP verb.
     *
     * @return $this
     */
    public function delete(): Router
    {
        $this->methods = ['DELETE'];
        $this->checkMethod();
        return $this;
    }

    /**
     * Matches the current route against any of the provided HTTP verbs.
     *
     * @param string[] ...$methods Array of verbs to match against. If none supplied, defaults to all.
     * @return $this
     */
    public function any(...$methods): Router
    {
        if (empty($methods) === true) {
            $this->methods = null;
            return $this;
        }

        $this->methods = [];

        foreach ($methods as $method) {
            $this->methods[] = \strtoupper($method);
        }

        return $this;
    }

    /**
     * If the matched route was a UrlRoute, this returns any matched params for that route.
     *
     * @return array
     */
    public function getRouteParams(): array
    {
        if (isset($this->current_route)) {
            return $this->current_route->parameters();
        }

        return [];
    }

    /**
     * Resets internal variables.
     */
    public function reset(): void
    {
        if ($this->has_matched_route === false) {
            $this->can_proceed = true;
            $this->current_route = null;
        }

        if ($this->is_group === false) {
            $this->namespace = '\\Theme\\Http\\Controllers\\';
            $this->methods = null;
            $this->middleware_queue = null;
        }
    }

    /**
     * Checks the current HTTP verb against the allowed verbs for the current route.
     */
    private function checkMethod(): void
    {
        if ($this->methods === null) {
            return;
        }

        if (!\in_array(Request::getMethod(), $this->methods, true)) {
            $this->stopProcessing();
        }
    }

    /**
     * Makes a new UrlRoute instance if needed.
     */
    private function makeUrlRoute(): void
    {
        if ($this->current_route === null) {
            $this->current_route = new UrlRoute();
        }
    }

    /**
     * Whether the router has already matched a route.
     *
     * @return bool
     */
    public function hasMatchedRoute(): bool
    {
        return $this->has_matched_route === true;
    }

    /**
     * If the current route can proceed processing.
     *
     * @return bool
     */
    private function canProceed(): bool
    {
        return $this->can_proceed === true && $this->hasMatchedRoute() === false;
    }

    /**
     * Stop the current route from processing any further.
     */
    private function stopProcessing(): void
    {
        $this->can_proceed = false;
    }

    /**
     * Set the current route as matched.
     */
    private function matchRoute(): void
    {
        $this->has_matched_route = true;

        if ($this->current_route) {
            Response::setStatus(200);
            /** @noinspection PhpUndefinedMethodInspection */
            Request::setCurrentRoute($this->current_route);
        }
    }

    /**
     * Maybe run middleware if needed, and descope current middleware stack if in group
     */
    private function maybeRunMiddleware(): void
    {
        if ($this->middleware_queue !== null && $this->canProceed() && $this->middleware_queue->passes() === false) {
            $this->can_proceed = false;
        }

        if ($this->middleware_queue !== null) {
            $this->middleware_queue->deScope();
        }
    }
}
