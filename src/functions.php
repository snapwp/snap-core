<?php

use Snap\Core\Snap;
use Snap\Services\Config;

/*
 * *********************************************************************************************************************
 * Class factories/container fetchers.
 * *********************************************************************************************************************
 */
if (!\function_exists('collect')) {
    /**
     * Return a new Collection instance.
     *
     * @param array $items Items to add.
     * @return \Tightenco\Collect\Support\Collection
     */
    function collect($items)
    {
        return new \Tightenco\Collect\Support\Collection($items);
    }
}

if (!\function_exists('snap_config')) {
    /**
     * Returns a key from the Config service.
     *
     * @param string $key     The option name to fetch.
     * @param mixed  $default If the option was not found, the default value to be returned instead.
     * @return mixed The option value, or default if not found.
     */
    function snap_config($key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!\function_exists('container')) {
    /**
     * Returns the service container
     *
     * @return \Hodl\Container The service container
     */
    function container()
    {
        return Snap::getContainer();
    }
}

if (!\function_exists('get_service')) {
    /**
     * Returns an object within the service container.
     *
     * @param string $key The service to fetch.
     * @return object An individual service.
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     */
    function get_service($key)
    {
        return Snap::getContainer()->get($key);
    }
}

if (!\function_exists('get_request')) {
    /**
     * Returns the current request instance.
     *
     * @return \Snap\Http\Request
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     */
    function get_request()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Snap::getContainer()->get('request');
    }
}


/*
 * *********************************************************************************************************************
 * Image helpers
 *
 * A collection of functions for getting meta information about image sizes.
 * *********************************************************************************************************************
 */
if (!\function_exists('snap_get_image_sizes')) {
    /**
     * Get size information for all currently registered image sizes.
     *
     * @return array Data for all currently registered image sizes.
     */
    function snap_get_image_sizes()
    {
        return \Snap\Utils\Image::getImageSizes();
    }
}

if (!\function_exists('snap_get_image_size')) {
    /**
     * Get size information for a specific image size.
     *
     * @param string $size The image size for which to retrieve data.
     * @return bool|array Size data about an image size or false if the size doesn't exist.
     */
    function snap_get_image_size($size)
    {
        return \Snap\Utils\Image::getImageSize($size);
    }
}

if (!\function_exists('snap_get_image_width')) {
    /**
     * Get the width of a specific image size.
     *
     * @param string $size The image size for which to retrieve data.
     * @return bool|int Width of an image size or false if the size doesn't exist.
     */
    function snap_get_image_width($size)
    {
        return \Snap\Utils\Image::getImageWidth($size);
    }
}

if (!\function_exists('snap_get_image_height')) {
    /**
     * Get the height of a specific image size.
     *
     * @param string $size The image size for which to retrieve data.
     * @return bool|int Height of an image size or false if the size doesn't exist.
     */
    function snap_get_image_height($size)
    {
        return \Snap\Utils\Image::getImageHeight($size);
    }
}


/*
 * *********************************************************************************************************************
 * Menu utilities
 * *********************************************************************************************************************
 */
if (!\function_exists('snap_get_nav_menu')) {
    /**
     * Returns a multi dimensional array of nav items for a given navigation menu.
     *
     * A better replacement for wp_get_nav_menu_items.
     *
     * @param string $theme_location The theme location the menu was registered with.
     * @return array
     */
    function snap_get_nav_menu($theme_location)
    {
        return \Snap\Utils\Menu::getNavMenu($theme_location);
    }
}

if (!\function_exists('snap_get_menu_name')) {
    /**
     * For a given menu ID, name, or slug, return the user-set name for the associated menu.
     *
     * @param string $theme_location Menu name, ID or slug
     * @return string|bool The name of the menu, or false if no menu was found
     */
    function snap_get_menu_name($theme_location)
    {
        return \Snap\Utils\Menu::getMenuName($theme_location);
    }
}


/*
 * *********************************************************************************************************************
 * User utilities
 * *********************************************************************************************************************
 */
if (!\function_exists('snap_get_user_role_name')) {
    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @param WP_User $user  A user to get the role of.
     *                       Defaults to current user.
     * @return string|bool The translated name of the current role, false if no role found.
     **/
    function snap_get_user_role_name($user = null)
    {
        return \Snap\Utils\User::getUserRoleName($user);
    }
}


/*
 * *********************************************************************************************************************
 * Sidebar utilities
 * *********************************************************************************************************************
 */
if (!\function_exists('snap_get_widget_count')) {
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * @param string $sidebar_id The ID of the sidebar.
     * @return int The count of the widgets in the sidebar.
     */
    function snap_get_widget_count($sidebar_id)
    {
        return \Snap\Utils\Sidebar::getWidgetCount($sidebar_id);
    }
}


/*
 * *********************************************************************************************************************
 * Theme utilities
 * *********************************************************************************************************************
 */
if (!\function_exists('snap_get_current_url')) {
    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @param boolean $remove_query If true, the URL is returned without any query params.
     * @return string The current URL.
     */
    function snap_get_current_url($remove_query = false)
    {
        return \Snap\Utils\Theme::getCurrentUrl($remove_query);
    }
}

if (!\function_exists('snap_get_asset_url')) {
    /**
     * Retrieves a filename public URL with Webpack version ID if present.
     *
     * @param string $file The asset file to look for.
     * @return string The (possibly versioned) asset URL.
     */
    function snap_get_asset_url($file)
    {
        return \Snap\Utils\Theme::getAssetUrl($file);
    }
}

if (!\function_exists('snap_is_post_template')) {
    /**
     * Whether the current request is the provided post template.
     *
     * @param string $post_template The template to check for.
     * @return bool
     */
    function snap_is_post_template($post_template): bool
    {
        return \Snap\Services\Request::isPostTemplate($post_template);
    }
}

if (!\function_exists('snap_is_wp_login')) {
    /**
     * Whether the current request is the login page or not.
     *
     * @return bool
     */
    function snap_is_wp_login(): bool
    {
        return \Snap\Services\Request::isLoginPage();
    }
}


/*
 * *********************************************************************************************************************
 * Template utilities
 * *********************************************************************************************************************
 */

/**
 * Get value of top level hierarchical post ID.
 *
 * Does not work with the objects returned by get_pages().
 *
 * @param int|WP_Post|array $post null Optional. Post object,array, or ID of a post to find the top ancestors for.
 * @return int ID
 */
function snap_get_top_parent_page_id($post = null): int
{
    return \Snap\Utils\View::getTopLevelParentId($post);
}

/**
 * Get current page depth.
 *
 * @param int|\WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
 * @return integer
 */
function snap_get_page_depth($page = null): int
{
    return \Snap\Utils\View::getPageDepth($page);
}


/*
 * *********************************************************************************************************************
 * URL utilities
 * *********************************************************************************************************************
 */

/**
 * Tests if a provided URL is external or not.
 *
 * @param string $url URL to test.
 * @return bool
 */
function snap_is_external_url(string $url): bool
{
    return \Snap\Utils\Url::isExternalUrl($url);
}

if (!\function_exists('leadingslashit')) {
    /**
     * Ensures a string begins with a slash.
     *
     * @param string $string
     * @return string
     */
    function leadingslashit(string $string): string
    {
        return \Snap\Utils\Url::leadingSlashIt($string);
    }
}

if (!\function_exists('untrailingslashit')) {
    /**
     * Removes any leading slashes from a string.
     *
     * @param string $string String to remove leading slashes for.
     * @return string
     */
    function is_external_url(string $string): string
    {
        return \Snap\Utils\Url::unLeadingSlashIt($string);
    }
}
