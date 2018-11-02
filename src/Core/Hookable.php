<?php

namespace Snap\Core;

use Snap\Core\Concerns\Manages_Hooks;

/**
 * Allows child classes to auto register hooks by simply defining them in an array
 * at the top of the class.
 *
 * Any class which extends Snap\Hookable is auto initialised upon inclusion.
 *
 * This forces a clean and readable pattern across all child classes.
 *
 * @since 1.0.0
 */
class Hookable
{
    use Manages_Hooks;

    /**
     * Filters to add on init.
     *
     * @since 1.0.0
     * @var array
     */
    protected $filters = [];

    /**
     * Actions to add on init.
     *
     * @since 1.0.0
     * @var array
     */
    protected $actions = [];

    /**
     * Run this hookable when is_admin returns true.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $admin = true;

    /**
     * Run this hookable when is_admin returns false.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $public = true;

    /**
     * Boot up the class.
     *
     * The hooks are registered, then boot is run.
     * This gives some extra options for conditionally adding filters.
     *
     * @since 1.0.0
     */
    final public function run()
    {
        if ($this->admin === false && \is_admin() === true) {
            return;
        }

        if ($this->public === false && \is_admin() === false) {
            return;
        }

        $this->parse_filters();
        $this->parse_actions();

        if (\method_exists($this, 'boot')) {
            $this->boot();
        }
    }

    /**
     * Returns the snake case version of the current Hookable class name.
     *
     * @since  1.0.0
     *
     * @return string
     */
    final protected function get_classname()
    {
        $classname = \basename(\str_replace(['\\', '_'], ['/', ''], \get_class($this)));
        $classname = \trim(\preg_replace('/([^_])(?=[A-Z])/', '$1_', $classname), '_');

        return \strtolower($classname);
    }

    /**
     * Add the hooks defined in $filters and $actions.
     *
     * @since 1.0.0
     *
     * @param array $hooks The contents of $filters or $actions.
     */
    final private function add_hooks($hooks)
    {
        foreach ($hooks as $tag => $filter) {
            if (\is_string($filter)) {
                $this->add_filter($tag, $filter);
            } else {
                foreach ($filter as $priority => $callbacks) {
                    if (\is_string($callbacks)) {
                        $this->add_filter($tag, $callbacks, $priority);
                    } else {
                        $count = \count($callbacks);

                        for ($i = 0; $i < $count; $i++) {
                            $this->add_filter($tag, $callbacks[ $i ], $priority);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check if $actions need to be added.
     *
     * @since 1.0.0
     */
    final private function parse_actions()
    {
        if (isset($this->actions) && \is_array($this->actions) && !empty($this->actions)) {
            $this->add_hooks($this->actions);
        }
    }

    /**
     * Check if $filters need to be added.
     *
     * @since 1.0.0
     */
    final private function parse_filters()
    {
        if (isset($this->filters) && \is_array($this->filters) && !empty($this->filters)) {
            $this->add_hooks($this->filters);
        }
    }
}
