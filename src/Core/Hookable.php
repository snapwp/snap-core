<?php

namespace Snap\Core;

use Snap\Core\Concerns\ManagesHooks;
use Snap\Utils\Str;

/**
 * Allows child classes to auto register hooks by simply defining them in an array
 * at the top of the class.
 *
 * Any class which extends Snap\Hookable is auto initialised upon inclusion.
 */
class Hookable
{
    use ManagesHooks;

    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Run this hookable when is_admin returns true.
     *
     * @var boolean
     */
    protected $admin = true;

    /**
     * Run this hookable when is_admin returns false.
     *
     * @var boolean
     */
    protected $public = true;

    /**
     * Optionally add hooks using methods instead of properties.
     *
     * Useful for conditionally adding hooks.
     */
    public function boot()
    {
        // Intended to be overwritten by child classes.
    }

    /**
     * Boot up the class.
     *
     * The hooks are registered, then boot is run.
     * This gives some extra options for conditionally adding filters.
     */
    final public function run(): void
    {
        if ($this->admin === false && \is_admin() === true) {
            return;
        }

        if ($this->public === false && \is_admin() === false) {
            return;
        }

        $this->parseFilters();
        $this->parseActions();

        $this->boot();
    }

    /**
     * Returns the snake _ase version of the current Hookable class name.
     *
     * @return string
     */
    final protected function getClassname(): string
    {
        $classname = \basename(\str_replace('\\', '/', \get_class($this)));
        return Str::toSnake($classname);
    }

    /**
     * Add the hooks defined in $filters and $actions.
     *
     * @param array $hooks The contents of $filters or $actions.
     */
    private function addHooks(array $hooks): void
    {
        foreach ($hooks as $tag => $filter) {
            if (\is_string($filter)) {
                $this->addFilter($tag, $filter);
            } else {
                foreach ($filter as $priority => $callbacks) {
                    if (\is_string($callbacks)) {
                        $this->addFilter($tag, $callbacks, $priority);
                    } else {
                        $count = \count($callbacks);

                        for ($i = 0; $i < $count; $i++) {
                            $this->addFilter($tag, $callbacks[$i], $priority);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check if $actions need to be added.
     */
    private function parseActions(): void
    {
        if (!empty($this->actions)) {
            $this->addHooks($this->actions);
        }
    }

    /**
     * Check if $filters need to be added.
     */
    private function parseFilters(): void
    {
        if (!empty($this->filters)) {
            $this->addHooks($this->filters);
        }
    }
}
