<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use Snap\Services\Container;
use Snap\Utils\User;

/**
 * A simple wrapper for auto registering AJAX actions.
 */
class AjaxHandler extends Hookable
{
    /**
     * The action to register.
     *
     * If not present, then the snake_case class name is used instead.
     *
     * @var null|string
     */
    protected $action = null;

    /**
     * If true then the AJAX action can be used by all users - logged in and otherwise.
     *
     * When false, only logged in users can call this action.
     *
     * @var boolean
     */
    protected $allow_public_access = true;

    /**
     * A list of roles which are allowed to perform this action.
     *
     * If this action is available to guests as well as authorised users, then this restriction is ignored.
     * An unauthorised user receives a 403 HTTP status as a response.
     *
     * @var array
     */
    protected $restrict_to_roles = [];

    /**
     * Run this hookable when is_admin returns false.
     *
     * @var boolean
     */
    protected $public = false;

    /**
     * Boot the AJAX Hookable, and register the handler.
     */
    public function boot()
    {
        $this->addAction("wp_ajax_{$this->getActionName()}", 'handler');

        if ($this->allow_public_access) {
            $this->addAction("wp_ajax_nopriv_{$this->getActionName()}", 'handler');
        }
    }

    /**
     * Auto-wire and call the child class's handle method.
     *
     */
    public function handler()
    {
        if ($this->allow_public_access) {
            Container::resolveMethod($this, 'handle');
            return;
        }

        if (empty($this->restrict_to_roles) || \in_array(User::getUserRole()->name, $this->restrict_to_roles)) {
            Container::resolveMethod($this, 'handle');
            return;
        }

        \wp_send_json_error('You do not have sufficient permissions to perform this action', 403);
    }

    /**
     * Return the unqualified snake case name of the current child class, or $action if set.
     *
     * @return string
     */
    private function getActionName()
    {
        if ($this->action === null) {
            return $this->getClassname();
        }

        return $this->action;
    }
}
