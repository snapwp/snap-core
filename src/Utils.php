<?php

namespace Snap\Core;

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
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of
     * @return string|bool The translated name of the current role, false if no role found
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
     * @param  boolean  $remove_query If true, the URL is returned without any query params.
     * @return string   The current URL.
     */
    public static function get_current_url($remove_query = false)
    {
        global $wp;

        if ($remove_query === true) {
            return trailingslashit(home_url($wp->request));
        }

        return home_url(add_query_arg(null, null));
    }

    /**
     * Get size information for all currently registered image sizes.
     *
     * @global $_wp_additional_image_sizes
     *
     * @since  1.0.0
     *
     * @return array Data for all currently registered image sizes.
     */
    static public function get_image_sizes()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $size) {
            if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                $sizes[ $size ] = [
                    'width' => get_option("{$size}_size_w"),
                    'height' => get_option("{$size}_size_h"),
                    'crop' => (bool) get_option("{$size}_crop"),
                ];
            } elseif (isset($_wp_additional_image_sizes[ $size ])) {
                $sizes[ $size ] = [
                    'width'  => $_wp_additional_image_sizes[ $size ]['width'],
                    'height' => $_wp_additional_image_sizes[ $size ]['height'],
                    'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
                ];
            }
        }

        return $sizes;
    }

    /**
     * Get size information for a specific image size.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|array Size data about an image size or false if the size doesn't exist.
     */
    static public function get_image_size($size)
    {
        $sizes = self::get_image_sizes();

        if (is_string($size) && isset($sizes[ $size ])) {
            return $sizes[ $size ];
        }

        return false;
    }

    /**
     * Get the width of a specific image size.
     *
     * Really useful for HTML img tags.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Width of an image size or false if the size doesn't exist.
     */
    public static function get_image_width($size)
    {
        // Get the size meta array.
        $size = self::get_image_size($size);

        if ($size === false) {
            return false;
        }

        if (isset($size['width'])) {
            return (int) $size['width'];
        }

        return false;
    }

    /**
     * Get the height of a specific image size.
     *
     * Really useful for HTML img tags.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Height of an image size or false if the size doesn't exist.
     */
    public static function get_image_height($size)
    {
        // Get the size meta array.
        $size = self::get_image_size($size);

        if ($size === false) {
            return false;
        }

        if (isset($size['height'])) {
            return (int) $size['height'];
        }

        return false;
    }

}
