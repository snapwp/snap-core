<?php

namespace Snap\Utils;

use WP_User;

/**
 * Provides some User utilities.
 */
class User
{
    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @param  WP_User $user Optional. A user to get the role of. Defaults to current user.
     * @return \WP_Role|false
     **/
    public static function getUserRole($user = null)
    {
        $wp_roles = \wp_roles();

        if ($user === null) {
            $user = \wp_get_current_user();
        }

        if (!($user instanceof WP_User)) {
            return false;
        }

        $roles = $user->roles;
        $role = \array_shift($roles);

        return isset($wp_roles->role_objects[$role]) ? $wp_roles->role_objects[$role] : false;
    }

    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @param  WP_User $user Optional. A user to get the role of. Defaults to current user.
     * @return string|bool The translated name of the current role, false if no role found.
     **/
    public static function getUserRoleName($user = null)
    {
        $wp_roles = \wp_roles();

        $role = static::getUserRole($user);

        if (isset($role->name)) {
            return isset($wp_roles->role_names[$role->name])
                ? \translate_user_role($wp_roles->role_names[$role->name])
                : false;
        }

        return false;
    }
}
