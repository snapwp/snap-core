<?php

namespace Snap\Core;

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
     * Run immediately after class instantiation.
     *
     * To be overridden by the child class.
     *
     * @since 1.0.0
     */
    protected function boot()
    {
    }

    /**
     * Run after class hooks have been added.
     *
     * To be overridden by the child class.
     *
     * @since 1.0.0
     */
    protected function after_boot()
    {
    }

    /**
     * Boot up the class.
     *
     * The boot method is run, then hooks are registered, then after_boot is run.
     * This gives some extra options for conditionally adding filters.
     *
     * @since 1.0.0
     */
    final public function run()
    {
        $this->boot();
        $this->parse_filters();
        $this->parse_actions();
        $this->after_boot();
    }

    /**
     * Lists debug info about all callbacks for a given hook.
     *
     * Returns information for all callbacks in order of execution and priority.
     *
     * @since  1.0.0
     *
     * @param  string $hook The hook to find callbacks for.
     * @return array        Array of priorities, each containing a nested array of callbacks.
     */
    final public static function debug_hook($hook = '')
    {
        global $wp_filter;

        $return = [];

        if (! is_string($hook) || ! isset($wp_filter[ $hook ]) || empty($wp_filter[ $hook ]->callbacks)) {
            return $return;
        }

        foreach ($wp_filter[ $hook ]->callbacks as $priority => $callbacks) {
            $return[ $priority ] = [];

            foreach ($callbacks as $key => $callback) {
                $function = $callback['function'];
                $args = $callback['accepted_args'];

                if (is_array($function)) {
                    // Is a class
                    if (is_callable([ $function[0], $function[1] ])) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Class Method',
                            new \ReflectionMethod($function[0], $function[1]),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                } elseif (is_object($function) && $function instanceof \Closure) {
                    // Is a closure.
                    $return[ $priority ][ $key ] = self::generate_hook_info(
                        'Closure',
                        new \ReflectionFunction($function),
                        $args
                    );
                } elseif (strpos($function, '::') !== false) {
                    // Is a static method.
                    list( $class, $method ) = explode('::', $function);

                    if (is_callable([ $class, $method ])) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Static Method',
                            new \ReflectionMethod($class, $method),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                } else {
                    // Is a function.
                    if (function_exists($function)) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Function',
                            new \ReflectionFunction($function),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Generate output for a hook callback.
     *
     * @since  1.0.0
     *
     * @param  string $type
     * @param  object $reflector The reflection object handling this callback.
     * @param  int    $args      The number of args defined when this callback was added.
     * @return array
     */
    final private static function generate_hook_info($type, $reflector, $args)
    {
        $output = [
            'type' => $type,
            'file_name' => $reflector->getFileName(),
            'line_number' => $reflector->getStartLine(),
            'class' => null,
            'name' => null,
            'is_internal' => false
        ];

        if ($reflector instanceof \ReflectionMethod) {
            $output['class'] = $reflector->getDeclaringClass()->getName();
        }

        if ('Closure' !== $type) {
            $output['name'] = $reflector->getName();
            $output['is_internal'] = $reflector->isInternal();
        }

        $output['accepted_args'] = $args;

        return $output;
    }

    /**
     * Generate output for an undefined callback.
     *
     * @since 1.0.0
     *
     * @return array
     */
    final private static function generate_undefined_hook_info()
    {
        return [
            'type' => 'Undefined',
            'file_name' => null,
            'line_number' => null,
            'class' => null,
            'name' => null,
            'is_internal' => null,
            'accepted_args' => 0,
        ];
    }

    /**
     * Syntactic sugar around add_filter for grouping hooks together based on type.
     *
     * @since 1.0.0
     *
     * @param string    $tag             The name of the filter to hook the $function_to_add callback to.
     * @param callable  $function_to_add The callback to be run when the filter is applied.
     * @param integer   $priority        The priority of the callback.
     * @param integer   $accepted_args   The amount of arguments the callback accepts.
     */
    final public function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        $this->add_filter($tag, $function_to_add, $priority, $accepted_args);
    }

    /**
     * A wrapper for add_filter. Multiple hooks can be passed as an array to apply the callback
     * to multiple filters within the same method call.
     *
     * If the supplied callback is from a child class, it will be bound to that instance automatically.
     *
     * @since 1.0.0
     *
     * @param string|array  $tag             The name of the filter to hook the $function_to_add callback to.
     *                                       Can also be an array of filters.
     * @param callable      $function_to_add The callback to be run when the filter is applied.
     * @param integer       $priority        The priority of the callback.
     * @param integer       $accepted_args   The amount of arguments the callback accepts.
     */
    final public function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        $callback = $function_to_add;

        if (is_string($function_to_add) && is_callable([ $this, $function_to_add ])) {
            // Bind the callback to the current child class.
            $callback = [ $this, $function_to_add ];
        }

        if (is_array($tag)) {
            // Add the callback to all provided hooks.
            foreach ($tag as $hook) {
                add_filter(
                    $hook,
                    $callback,
                    $priority ? $priority : 10,
                    $this->get_argument_count($function_to_add, $accepted_args)
                );
            }
        } else {
            add_filter(
                $tag,
                $callback,
                $priority ? $priority : 10,
                $this->get_argument_count($function_to_add, $accepted_args)
            );
        }
    }

    /**
     * Syntactic sugar around remove_hook.
     *
     * @see  \Snap\Core\Hookable::remove_hook
     * @since  1.0.0
     *
     * @param  string|array  $tag                The hook(s) to remove the callback from.
     * @param  callable      $function_to_remove The callback to remove.
     * @param  integer       $priority           Optional. The priority of the callback to remove. Defaults to 10.
     */
    final public function remove_action($tag, $function_to_remove, $priority = 10)
    {
        $this->remove_hook($tag, $function_to_remove, $priority);
    }

    /**
     * Syntactic sugar around remove_hook.
     *
     * @see  \Snap\Core\Hookable::remove_hook
     * @since  1.0.0
     *
     * @param  string|array  $tag                The hook(s) to remove the callback from.
     * @param  callable      $function_to_remove The callback to remove.
     * @param  integer       $priority           Optional. The priority of the callback to remove. Defaults to 10.
     */
    final public function remove_filter($tag, $function_to_remove, $priority = 10)
    {
        $this->remove_hook($tag, $function_to_remove, $priority);
    }

    /**
     * Removes a the given callback from a specific hook.
     *
     * @since  1.0.0
     *
     * @param  string|array  $tag                The hook(s) to remove the callback from.
     * @param  callable      $function_to_remove The callback to remove.
     * @param  integer       $priority           Optional. The priority of the callback to remove. Defaults to 10.
     */
    final public function remove_hook($tag, $function_to_remove, $priority = 10)
    {
        if (is_string($function_to_remove) && is_callable([ $this, $function_to_remove ])) {
            $function_to_remove = [ $this, $function_to_remove ];
        }

        if (is_array($tag)) {
            foreach ($tag as $hook) {
                remove_filter($hook, $function_to_remove, $priority);
            }
        } else {
            remove_filter($tag, $function_to_remove, $priority);
        }
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
            if (is_string($filter)) {
                $this->add_filter($tag, $filter);
            } else {
                foreach ($filter as $priority => $callbacks) {
                    if (is_string($callbacks)) {
                        $this->add_filter($tag, $callbacks, $priority);
                    } else {
                        $count = count($callbacks);

                        for ($i = 0; $i < $count; $i++) {
                            $this->add_filter($tag, $callbacks[ $i ], $priority);
                        }
                    }
                }
            }
        }
    }

    /**
     * Use reflection to count the amount of arguments a hook callback expects.
     *
     * @since 1.0.0
     *
     * @param  callable $callback      Closure or function name.
     * @param  integer  $accepted_args The amount of arguments passed into the hook.
     * @return integer
     */
    final private function get_argument_count($callback, $accepted_args = 1)
    {
        if (is_string($callback) && is_callable([ $this, $callback ])) {
            return ( new \ReflectionMethod($this, $callback) )->getNumberOfParameters();
        } elseif (is_object($callback) && $callback instanceof \Closure) {
            return ( new \ReflectionFunction($callback) )->getNumberOfParameters();
        } else {
            return $accepted_args ? $accepted_args : 1;
        }
    }

    /**
     * Check if $actions need to be added.
     *
     * @since 1.0.0
     */
    final private function parse_actions()
    {
        if (isset($this->actions) && is_array($this->actions) && ! empty($this->actions)) {
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
        if (isset($this->filters) && is_array($this->filters) && ! empty($this->filters)) {
            $this->add_hooks($this->filters);
        }
    }
}
