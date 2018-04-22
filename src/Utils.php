<?php

namespace Snap\Modules;

/**
 * A collection of useful helper functions.
 *
 * @since 1.0.0
 */
class Utils
{
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * Useful for applying classes to a parent container.
     *
     * @since  1.0.0
     *
     * @param  string $sidebar_id The ID of the sidebar.
     * @return int The count of the widgets in the sidebar.
     */
    public static function get_widget_count($sidebar_id)
    {
        global $_wp_sidebars_widgets;
        
        // If not front page, the global is empty so set it another way.
        if (empty($_wp_sidebars_widgets)) {
            $_wp_sidebars_widgets = get_option('sidebars_widgets', []);
        }
        
        // if our sidebar exists, return count.
        if (isset($_wp_sidebars_widgets[ $sidebar_id ])) {
            return count($_wp_sidebars_widgets[ $sidebar_id ]);
        }
        
        return 0;
    }

    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * Examples:
     * echo fat_get_user_role();
     * echo fat_get_user_role( $user );
     * echo fat_get_user_role( get_user_by( 'login', 'user123' ) );
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of
     * @return string|bool   The translated name of the current role, false if no role found
     **/
    public static function get_user_role($user = null)
    {
        global $wp_roles;

        if (! $user) {
            $user = wp_get_current_user();
        }
        
        $roles = $user->roles;
        $role = array_shift($roles);
        
        return isset($wp_roles->role_names[ $role ]) ? translate_user_role($wp_roles->role_names[ $role ]) : false;
    }

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @since  1.0.0
     *
     * @param  boolean  $remove_query  If true, the URL is returned without any query params.
     * @return string                  The current URL.
     */
    public static function get_current_url($remove_query = false)
    {
        global $wp;

        if ($remove_query === true) {
            return trailingslashit(home_url($wp->request));
        }

        return home_url(add_query_arg(null, null));
    }
}
