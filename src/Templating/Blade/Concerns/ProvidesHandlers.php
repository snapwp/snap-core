<?php

namespace Snap\Templating\Blade\Concerns;

use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Services\View;

/**
 * Handlers used by the WordPress specific versions of @auth, @can, @inject, @error and @csrf.
 *
 * Each handler can be swapped out using the matching set*Handler method.
 */
trait ProvidesHandlers
{
    /**
     * @var callable|null
     */
    private $auth_handler;

    /**
     * @var callable|null
     */
    private $inject_handler;

    /**
     * @var callable|null
     */
    private $can_handler;

    /**
     * @var callable|null
     */
    private $error_handler;

    /**
     * @var callable|null
     */
    private $csrf_handler;

    /**
     * Set handler to generate csrf tokens.
     *
     * @param callable $handler
     */
    public function setCsrfHandler(callable $handler): void
    {
        $this->csrf_handler = $handler;
    }

    /**
     * Set handler to resolve @auth directives.
     *
     * @param callable $handler
     */
    public function setAuthHandler(callable $handler): void
    {
        $this->auth_handler = $handler;
    }

    /**
     * Set handler to resolve @can directives.
     *
     * @param callable $handler
     */
    public function setCanHandler(callable $handler): void
    {
        $this->can_handler = $handler;
    }

    /**
     * Set the handler to resolve services via @inject directive.
     *
     * @param callable $handler
     */
    public function setInjectHandler(callable $handler): void
    {
        $this->inject_handler = $handler;
    }

    /**
     * Set the handler to run via the @error directive.
     *
     * @param callable $handler
     */
    public function setErrorHandler(callable $handler): void
    {
        $this->error_handler = $handler;
    }

    /**
     * Return the current user's csrf token.
     *
     * @return string
     */
    public function getCsrfToken(): string
    {
        return \call_user_func($this->csrf_handler ?? [$this, 'defaultCsrfHandler']);
    }

    /**
     * @param string|null $guard
     * @return bool
     */
    public function authHandler(?string $guard = null): bool
    {
        return \call_user_func($this->auth_handler ?? [$this, 'defaultAuthHandler'], $guard);
    }

    /**
     * @param string|array $abilities
     * @param mixed        $arguments
     * @return bool
     */
    public function canHandler($abilities, $arguments = null): bool
    {
        return \call_user_func($this->can_handler ?? [$this, 'defaultCanHandler'], $abilities, $arguments);
    }

    /**
     * @param string|array $abilities
     * @param mixed        $arguments
     * @return bool
     */
    public function canAnyHandler($abilities, $arguments = null): bool
    {
        foreach ((array) $abilities as $ability) {
            if ($this->canHandler($ability, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $service
     * @return mixed
     */
    public function injectHandler(string $service)
    {
        return \call_user_func($this->inject_handler ?? [$this, 'defaultInjectHandler'], $service);
    }

    /**
     * @param string $error
     * @return mixed
     */
    public function errorHandler(string $error)
    {
        return \call_user_func($this->error_handler ?? [$this, 'defaultErrorHandler'], $error);
    }

    /**
     * Default CSRF token generation.
     *
     * @return string
     */
    public function defaultCsrfHandler(): string
    {
        return \wp_create_nonce(View::getCurrentView());
    }

    /**
     * Default auth handler.
     *
     * @param string|null $guard
     * @return bool
     */
    protected function defaultAuthHandler(?string $guard = null): bool
    {
        return $guard ? \current_user_can($guard) : \is_user_logged_in();
    }

    /**
     * Default can handler.
     *
     * @param string|array $abilities
     * @param mixed        $arguments
     * @return bool
     */
    protected function defaultCanHandler($abilities, $arguments = null): bool
    {
        if ($arguments === null) {
            return \current_user_can($abilities);
        }

        return \current_user_can($abilities, ...(\is_array($arguments) ? $arguments : [$arguments]));
    }

    /**
     * Default service injection handler.
     *
     * @param string $service
     * @return object
     */
    protected function defaultInjectHandler(string $service)
    {
        return Container::get($service);
    }

    /**
     * Default error handler.
     *
     * @param string $error
     * @return string|false
     */
    protected function defaultErrorHandler(string $error)
    {
        return Request::getGlobalErrors()->first($error) ?? false;
    }
}
