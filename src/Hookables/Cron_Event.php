<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use Snap\Services\Container;

/**
 * A simple wrapper for auto registering AJAX actions.
 *
 * @since 1.0.0
 */
class Cron_Event extends Hookable
{
    /**
     * The action to register.
     *
     * If not present, then snap_cron_{snake-case classname} is used instead.
     *
     * @since 1.0.0
     * @var null|string
     */
    protected $action = null;

    /**
     * The schedule to run this cron at.
     *
     * @since 1.0.0
     * @var string
     */
    protected $schedule = 'hourly';

    /**
     * Boot the AJAX Hookable, and register the handler.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        $this->addAction($this->get_cron_action(), 'handler');

        // Update the interval if the schedule has changed since first addition.
        if (\wp_get_schedule($this->get_cron_action()) !== $this->schedule) {
            \wp_clear_scheduled_hook($this->get_cron_action());
        }

        if (! \wp_next_scheduled($this->get_cron_action())) {
            \wp_schedule_event(\time(), $this->schedule, $this->get_cron_action());
        }
    }

    /**
     * Auto-wire and call the child class's handle method.
     *
     * @since 1.0.0
     */
    final public function handler()
    {
        Container::resolveMethod($this, 'handle');
    }

    /**
     * Return the unqualified snake case name of the current child class, or $action if set.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_cron_action()
    {
        if ($this->action === null) {
            return 'snap_cron_' . $this->getClassname();
        }

        return $this->action;
    }
}
