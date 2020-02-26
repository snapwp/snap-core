<?php

namespace Snap\Templating\Blade;

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
}
