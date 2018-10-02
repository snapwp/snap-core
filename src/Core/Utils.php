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
            return \count($_wp_sidebars_widgets[ $sidebar_id ]);
        }
        
        return 0;
    }

    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of.
     * @return string|bool The translated name of the current role, false if no role found.
     **/
    public static function get_user_role($user = null)
    {
        global $wp_roles;

        if (! $user) {
            $user = wp_get_current_user();
        }
        
        $roles = $user->roles;
        $role = \array_shift($roles);
        
        return isset($wp_roles->role_names[ $role ]) ? translate_user_role($wp_roles->role_names[ $role ]) : false;
    }

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @since  1.0.0
     *
     * @param  boolean $remove_query If true, the URL is returned without any query params.
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
     * Get value of top level hierarchical post ID.
     *
     * Does not work with the objects returned by get_pages().
     *
     * @since  1.0.0
     *
     * @param (int|WP_Post|array) $post null Optional. Post object,array, or ID of a post to find the top ancestors for.
     * @return int ID
     */
    public static function get_top_level_parent_id($post = null)
    {
        if (is_search() || is_404()) {
            return null;
        }
        
        switch ($post) {
            // No post has been set, so use global.
            case null:
                global $post;

            // The post ID has been provided.
            case \is_int($post):
                $post = get_post($post);

            // A WP_Post was provided.
            case \is_object($post):
                $ancestors = $post->ancestors;
                break;

            case \is_array($post):
                $ancestors = $post['ancestors'];
                break;
        }
     
        if ($ancestors && ! empty($ancestors)) {
            return (int) \end($ancestors);
        } else {
            return (int) $post->ID;
        }
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
    public static function get_image_sizes()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $size) {
            if (\in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
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
    public static function get_image_size($size)
    {
        $sizes = self::get_image_sizes();

        if (\is_string($size) && isset($sizes[ $size ])) {
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

    /**
     * Get current page depth.
     *
     * @since  1.0.0
     *
     * @param int|WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
     * @return integer
     */
    public static function get_page_depth($page = null)
    {
        if ($page === null) {
            global $wp_query;
        
            $object = $wp_query->get_queried_object();
        } else {
            $object = get_post($page);
        }

        $parent_id  = $object->post_parent;
        $depth = 0;

        while ($parent_id > 0) {
            $page = get_page($parent_id);
            $parent_id = $page->post_parent;
            $depth++;
        }
     
        return $depth;
    }

    /**
     * Returns a multi dimensional array of nav items for a given navigation menu.
     *
     * @since  1.0.0
     *
     * @param  string $theme_location The theme location the menu was registered with.
     * @return array {
     *      @type string $ID               The ID of the post.
     *      @type string $text             The link text.
     *      @type string $title            The title attribute.
     *      @type string $description      Any description text added via the nav-menus admin.
     *      @type string $target           The link target.
     *      @type string $custom_classes   Any clases added via the nav-menus admin.
     *      @type string $url              The href of this link.
     *      @type array  $children         The link's children.
     *      @type bool   $has_children     Whether this link has children.
     *      @type bool   $is_active        Whether this link is the current link.
     *      @type bool   $has_active_child Whether a child link is the current link.
     * }
     */
    public static function get_nav_menu($theme_location = 'primary')
    {
        $menu = [];

        if (($theme_location) && ($locations = get_nav_menu_locations()) && isset($locations[ $theme_location ])) {
            $term = get_term($locations[ $theme_location ], 'nav_menu');

            $array_menu = wp_get_nav_menu_items($term->term_id);

            $current_id = get_the_id();

            foreach ($array_menu as $m) {
                $menu[ $m->ID ] = (object) [];
                $menu[ $m->ID ]->ID = $m->object_id;
                $menu[ $m->ID ]->text = $m->title;
                $menu[ $m->ID ]->title = $m->attr_title;
                $menu[ $m->ID ]->description = $m->description;
                $menu[ $m->ID ]->target = $m->target;
                $menu[ $m->ID ]->custom_classes = \implode(' ', $m->classes);
                $menu[ $m->ID ]->url = $m->url;
                $menu[ $m->ID ]->children = [];
                $menu[ $m->ID ]->has_children = false;
                $menu[ $m->ID ]->is_active = $m->object_id == $current_id;
                $menu[ $m->ID ]->has_active_child = false;
            }

            foreach (\array_reverse($array_menu) as $m) {
                if ($m->menu_item_parent !== '0') {
                    $menu[ $m->menu_item_parent ]->children[ $m->ID ] = $menu[ $m->ID ];

                    if ($menu[ $m->ID ]->is_active || $menu[ $m->ID ]->has_active_child) {
                        $menu[ $m->menu_item_parent ]->has_active_child = true;
                    }

                    unset($menu[ $m->ID ]);
                }
            }
        }

        return $menu;
    }
}
