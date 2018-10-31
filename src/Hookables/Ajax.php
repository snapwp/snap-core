<?php

namespace Snap\Hookables;

use Snap\Core\Snap;
use Snap\Core\Utils;
use Snap\Core\Hookable;
use Snap\Utils\User_Utils;

/**
 * A simple wrapper for auto registering AJAX actions.
 *
 * @since 1.0.0
 */
class Ajax extends Hookable
{
    /**
     * The action to register.
     *
     * If not present, then the snake cased class name is used instead.
     *
     * @since 1.0.0
     * @var null|string
     */
    protected $action = null;

    /**
     * If true then the AJAX action can be used by all users - logged in and otherwise.
     *
     * When false, only logged in users can call this action.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $allow_public_access = true;

    /**
     * A list of roles which are allowed to perform this action.
     *
     * If this action is available to guests as well as authorised users, then this restriction is ignored.
     *
     * An unauthorised user receives a 403 HTTP status as a response.
     *
     * @since 1.0.0
     * @var array
     */
    protected $restrict_to_roles = [
        'Administrator',
        'Editor',
        'Author',
        'Contributor',
        'Subscriber',
    ];

    /**
     * Run this hookable when is_admin returns false.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $public = false;

    /**
     * Boot the AJAX Hookable, and register the handler.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        $this->add_action("wp_ajax_{$this->get_action_name()}", 'handler');

        if ($this->allow_public_access) {
            $this->add_action("wp_ajax_nopriv_{$this->get_action_name()}", 'handler');
        }
    }

    /**
     * Auto-wire and call the child class's handle method.
     *
     * @since 1.0.0
     *
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \ReflectionException
     */
    public function handler()
    {
        if (\in_array(User_Utils::get_user_role_name(), $this->restrict_to_roles) || $this->allow_public_access) {
            Snap::services()->resolveMethod($this, 'handle');
        }

        wp_send_json_error('You do not have sufficient permissions to perform this action', 403);
    }

    /**
     * Return the unqualified snake case name of the current child class, or $action if set.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_action_name()
    {
        if ($this->action === null) {
            return $this->get_classname();
        }

        return $this->action;
    }
}
