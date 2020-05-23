<?php

namespace Snap\Templating\Blade;

use Snap\Services\Container;
use Snap\Services\Request;
use Snap\Services\View;

class Factory extends \Bladezero\Factory
{
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $path
     * @param array  $data
     * @param array  $mergeData
     * @return string
     * @throws \Throwable
     */
    public function file($path, array $data = [], array $mergeData = []): string
    {
        $data = \array_merge(
            $this->getShared(),
            $mergeData,
            View::getAdditionalData(View::normalizePath($path)),
            $this->parseData($data)
        );

        return $this->render($path, $data);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     * @return string
     * @throws \Throwable
     */
    public function make($view, $data = [], $mergeData = [])
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view)
        );

        // Next, we will create the view instance and call the view creator for the view
        // which can set any data, etc. Then we will return the view instance back to
        // the caller for rendering or performing other view manipulations on this.
        $data = \array_merge(
            $this->getShared(),
            $mergeData,
            View::getAdditionalData(View::normalizePath($path)),
            $this->parseData($data)
        );

        return $this->render($path, $data);
    }

    /**
     * Default CSRF token generation.
     *
     * A real implementation should save this value to the session or some other store to allow validation.
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
    protected function defaultAuthHandler(string $guard = null): bool
    {
        return \current_user_can($guard);
    }

    /**
     * Default can handler.
     *
     * @param string|array $abilities
     * @param string|array $arguments
     * @return bool
     */
    protected function defaultCanHandler($abilities, $arguments = null): bool
    {
        return \current_user_can($abilities, $arguments);
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
