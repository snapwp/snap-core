<?php

use Snap\Core\Snap;

/*
 * *********************************************************************************************************************
 * Utility functions
 *
 * Some useful functions to make everyday WordPress life easier.
 * *********************************************************************************************************************
 */
if (! \function_exists('services')) {
    /**
     * Returns the service container
     *
     * @since  1.0.0
     *
     * @return mixed The service container or an individual service.
     */
    function services()
    {
        return Snap::services();
    }
}

if (! \function_exists('get_service')) {
    /**
     * Returns the service container, or an object within it.
     *
     * @since  1.0.0
     *
     * @param  string $key The service to fetch.
     * @return mixed An individual service.
     */
    function get_service($key)
    {
        return Snap::services()->get($key);
    }
}

/*
 * *********************************************************************************************************************
 * Class factories/container fetchers.
 * *********************************************************************************************************************
 */
if (! \function_exists('collect')) {
    /**
     * Return a new Collection instance.
     *
     * @since  1.0.0
     *
     * @param array $items Items to add.
     * @return \Snap\Utils\Collection
     */
    function collect($items)
    {
        return new \Snap\Utils\Collection($items);
    }
}

if (! \function_exists('config')) {
    /**
     * Returns a key from the Config service.
     *
     * @since  1.0.0
     *
     * @param  string $key     The option name to fetch.
     * @param  mixed  $default If the option was not found, the default value to be returned instead.
     * @return mixed The option value, or default if not found.
     */
    function config($key, $default = null)
    {
        return Snap::config()->get($key, $default);
    }
}


/*
 * *********************************************************************************************************************
 * Image helpers
 *
 * A collection of functions for getting meta information about image sizes.
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_image_sizes')) {
    /**
     * Get size information for all currently registered image sizes.
     *
     * @since  1.0.0
     *
     *  @return array Data for all currently registered image sizes.
     */
    function snap_get_image_sizes()
    {
        return \Snap\Utils\Image_Utils::get_image_sizes();
    }
}

if (! \function_exists('snap_get_image_size')) {
    /**
     * Get size information for a specific image size.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|array Size data about an image size or false if the size doesn't exist.
     */
    function snap_get_image_size($size)
    {
        return \Snap\Utils\Image_Utils::get_image_size($size);
    }
}

if (! \function_exists('snap_get_image_width')) {
    /**
     * Get the width of a specific image size.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Width of an image size or false if the size doesn't exist.
     */
    function snap_get_image_width($size)
    {
        return \Snap\Utils\Image_Utils::get_image_width($size);
    }
}

if (! \function_exists('snap_get_image_height')) {
    /**
     * Get the height of a specific image size.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Height of an image size or false if the size doesn't exist.
     */
    function snap_get_image_height($size)
    {
        return \Snap\Utils\Image_Utils::get_image_height($size);
    }
}


/*
 * *********************************************************************************************************************
 * Menu utilities
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_nav_menu')) {
    /**
     * Returns a multi dimensional array of nav items for a given navigation menu.
     *
     * A better replacement for wp_get_nav_menu_items.
     *
     * @since  1.0.0
     *
     * @param  string $theme_location The theme location the menu was registered with.
     * @return array
     */
    function snap_get_nav_menu($theme_location)
    {
        return \Snap\Utils\Menu_Utils::get_nav_menu($theme_location);
    }
}

if (! \function_exists('snap_get_menu_name')) {
    /**
     * For a given menu ID, name, or slug, return the user-set name for the associated menu.
     *
     * @since 1.0.0
     *
     * @param  string $theme_location Menu name, ID or slug
     * @return string|bool The name of the menu, or false if no menu was found
     */
    function snap_get_menu_name($theme_location)
    {
        return \Snap\Utils\Menu_Utils::get_menu_name($theme_location);
    }
}


/*
 * *********************************************************************************************************************
 * User utilities
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_user_role_name')) {
    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of.
     *                       Defaults to current user.
     * @return string|bool The translated name of the current role, false if no role found.
     **/
    function snap_get_user_role_name($user = null)
    {
        return \Snap\Utils\User_Utils::get_user_role_name($user);
    }
}


/*
 * *********************************************************************************************************************
 * Sidebar utilities
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_widget_count')) {
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * @since  1.0.0
     *
     * @param  string $sidebar_id The ID of the sidebar.
     * @return int The count of the widgets in the sidebar.
     */
    function snap_get_widget_count($sidebar_id)
    {
        return \Snap\Utils\Sidebar_Utils::get_widget_count($sidebar_id);
    }
}


/*
 * *********************************************************************************************************************
 * Theme utilities
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_current_url')) {
    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @since  1.0.0
     *
     * @param  boolean $remove_query If true, the URL is returned without any query params.
     * @return string The current URL.
     */
    function snap_get_current_url($remove_query = false)
    {
        return \Snap\Utils\Theme_Utils::get_current_url($remove_query);
    }
}

if (! \function_exists('snap_get_asset_url')) {
    /**
     * Retrieves a filename public URL with Webpack version ID if present.
     *
     * @since  1.0.0
     *
     * @param  string $file The asset file to look for.
     * @return string The (possibly versioned) asset URL.
     */
    function snap_get_asset_url($file)
    {
        return \Snap\Utils\Theme_Utils::get_asset_url($file);
    }
}


/*
 * *********************************************************************************************************************
 * Template utilities
 * *********************************************************************************************************************
 */
if (! \function_exists('snap_get_top_parent_page_id')) {
    /**
     * Get value of top level hierarchical post ID.
     *
     * Does not work with the objects returned by get_pages().
     *
     * @since  1.0.0
     *
     * @param int|WP_Post|array $post null Optional. Post object,array, or ID of a post to find the top ancestors for.
     * @return int ID
     */
    function snap_get_top_parent_page_id($post = null)
    {
        return \Snap\Utils\View_Utils::get_top_level_parent_id($post);
    }
}

if (! \function_exists('snap_get_page_depth')) {
    /**
     * Get current page depth.
     *
     * @since  1.0.0
     *
     * @param int|\WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
     * @return integer
     */
    function snap_get_page_depth($page = null)
    {
        return \Snap\Utils\View_Utils::get_page_depth($page);
    }
}
