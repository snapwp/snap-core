<?php

namespace Snap\Utils;

use Tightenco\Collect\Support\Collection;

/**
 * Utilities for nav menus.
 */
class Menu
{
    /**
     * Returns a multi dimensional array of nav items for a given navigation menu.
     *
     * A better replacement for wp_get_nav_menu_items.
     *
     * @param  string $theme_location   The theme location the menu was registered with.
     * @return array {
     * @type string   $ID               The ID of the post.
     * @type string   $text             The link text.
     * @type string   $title            The title attribute.
     * @type string   $description      Any description text added via the nav-menus admin.
     * @type string   $target           The link target.
     * @type string   $custom_classes   Any classes added via the nav-menus admin.
     * @type string   $url              The href of this link.
     * @type array    $children         The link's children.
     * @type bool     $has_children     Whether this link has children.
     * @type bool     $is_active        Whether this link is the current link.
     * @type bool     $has_active_child Whether a child link is the current link.
     * }
     */
    public static function getNavMenu(string $theme_location = 'primary'): array
    {
        $menu = [];
        $term = static::getMenuObject($theme_location);

        if ($term !== false) {
            $menu_items_raw = \wp_get_nav_menu_items($term->term_id);

            // Collect the menu items.
            $menu_items = new Collection($menu_items_raw);

            // Create a flat array of non WP classes on these menu items.
            $custom_classes = $menu_items->pluck('classes')->flatten()->filter()->all();

            // Apply WP classes to the menu items. Subject to change.
            \_wp_menu_item_classes_by_context($menu_items_raw);

            $menu = [];
            $refs = [];

            foreach ($menu_items as $item) {
                if ($item->menu_item_parent === '0') {
                    $menu[$item->ID] = static::generateMenuObject($item, $custom_classes);
                    $refs[$item->ID] = $menu[$item->ID];
                } else {
                    if (isset($refs[(int)$item->menu_item_parent])) {
                        $refs[(int)$item->menu_item_parent]->children[$item->ID] = static::generateMenuObject($item, $custom_classes);

                        $refs[$item->ID] = $refs[(int)$item->menu_item_parent]->children[$item->ID];
                        $refs[(int)$item->menu_item_parent]->has_children = true;
                    }
                }
            }
        }

        return $menu;
    }

    /**
     * Returns a menu object for a given theme location.
     *
     * @param  string $theme_location Menu name.
     * @return \WP_Term|false Menu Object on success, false on failure.
     */
    public static function getMenuObject(string $theme_location)
    {
        $locations = \get_nav_menu_locations();

        if (isset($locations[$theme_location])) {
            return \wp_get_nav_menu_object($locations[$theme_location]);
        }

        return false;
    }

    /**
     * For a given menu ID, name, or slug, return the user-set name for the associated menu.
     *
     * @param  string $theme_location Menu name, ID or slug.
     * @return string|bool The name of the menu, or false if no menu was found.
     */
    public static function getMenuName(string $theme_location)
    {
        $menu_obj = static::getMenuObject($theme_location);

        if ($menu_obj !== false) {
            return $menu_obj->name;
        }

        return false;
    }

    /**
     * Create a simple object from a wp_get_nav_menu_object response.
     *
     * @param object $item Source object.
     * @param array $custom_classes Current allowed custom classes.
     * @return object
     */
    private static function generateMenuObject(object $item, array $custom_classes): object
    {
        $is_active = false;
        $has_active_child = false;

        $item_classes = \array_intersect($item->classes, $custom_classes);

        if (\in_array('current-menu-item', $item->classes)) {
            $is_active = true;
        }

        if (\in_array('current-menu-ancestor', $item->classes)) {
            $has_active_child = true;
        }

        $output = [
            'ID' => $item->ID,
            'object_id' => $item->object_id,
            'text' => $item->title,
            'title' => $item->attr_title,
            'description' => $item->description,
            'target' => $item->target,
            'custom_classes' => \implode(' ', $item_classes),
            'url' => $item->url,
            'children' => [],
            'has_children' => false,
            'is_active' => $is_active,
            'has_active_child' => $has_active_child,
        ];

        return (object)$output;
    }
}
