<?php

namespace Snap\Utils;

use WP_User;

/**
 * Provides some User utilities.
 *
 * @since 1.0.0
 */
class User_Utils
{
    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of.
     *                       Defaults to current user.
     * @return \WP_Role|false
     **/
    public static function get_user_role($user = null)
    {
        $wp_roles = wp_roles();

        if ($user === null) {
            $user = wp_get_current_user();
        }

        if (!($user instanceof WP_User)) {
            return false;
        }

        $roles = $user->roles;
        $role = \array_shift($roles);

        return isset($wp_roles->role_objects[ $role ]) ? $wp_roles->role_objects[ $role ] : false;
    }

    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of.
     *                       Defaults to current user.
     * @return string|bool The translated name of the current role, false if no role found.
     **/
    public static function get_user_role_name($user = null)
    {
        $wp_roles = wp_roles();

        $role = static::get_user_role($user);

        if (isset($role->name)) {
            return isset($wp_roles->role_names[ $role->name ]) ? translate_user_role($wp_roles->role_names[ $role->name ]) : false;
        }

        return false;
    }
}
