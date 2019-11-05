<?php

namespace Snap\Routing;

use Snap\Services\Request;

class UrlRoute
{
    /**
     * Whether this route is a match.
     *
     * @var bool
     */
    private $matches = false;

    /**
     * URL parts.
     *
     * @var array
     */
    private $parts = [];

    /**
     * URL params.
     *
     * @var array
     */
    private $params = [];

    /**
     * Add the URL to match against.
     *
     * @param string $url URL to match.
     * @return $this
     */
    public function addUrl(string $url): UrlRoute
    {
        $url = \trim($url, '/');

        $this->parts = \explode('/', $url);

        if (\count($this->parts) !== \count(Request::getPathSegments())) {
            return $this;
        }

        foreach ($this->parts as $n => $part) {
            if (\strpos($part, '{') === 0) {
                $this->params[\trim($part, '{}')] = Request::getPathSegments()[$n];
                continue;
            }

            if ($part !== Request::getPathSegments()[$n]) {
                return $this;
            }
        }

        $this->matches = true;

        return $this;
    }

    /**
     * Add regex tests for the current params.
     *
     * @param array $map Map of tests.
     * @return $this
     */
    public function addTests(array $map): UrlRoute
    {
        foreach ($map as $key => $test) {
            if (!isset($this->params[$key])) {
                continue;
            }

            if (\preg_match('#^' . $test . '$#', $this->params[$key]) !== 1) {
                $this->matches = false;
                break;
            }
        }

        return $this;
    }

    /**
     * Return any matched URL params.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Whether this route is a match against the current request.
     *
     * @return bool
     */
    public function isMatch(): bool
    {
        return $this->matches;
    }
}
