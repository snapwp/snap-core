<?php

namespace Snap\Utils;

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
     *                                  }
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

            foreach ($menu_items as $menu_item) {
                $is_active = false;

                $item_classes = \array_intersect($menu_item->classes, $custom_classes);

                if (\in_array('current-menu-item', $menu_item->classes)) {
                    $is_active = true;
                }

                $menu[$menu_item->ID] = (object)[];
                $menu[$menu_item->ID]->ID = $menu_item->object_id;
                $menu[$menu_item->ID]->text = $menu_item->title;
                $menu[$menu_item->ID]->title = $menu_item->attr_title;
                $menu[$menu_item->ID]->description = $menu_item->description;
                $menu[$menu_item->ID]->target = $menu_item->target;
                $menu[$menu_item->ID]->custom_classes = \implode(' ', $item_classes);
                $menu[$menu_item->ID]->url = $menu_item->url;
                $menu[$menu_item->ID]->children = [];
                $menu[$menu_item->ID]->has_children = false;
                $menu[$menu_item->ID]->is_active = $is_active;
                $menu[$menu_item->ID]->has_active_child = false;
            }

            foreach ($menu_items->reverse() as $menu_item) {
                if ($menu_item->menu_item_parent !== '0') {
                    $menu[$menu_item->menu_item_parent]->children[$menu_item->ID] = $menu[$menu_item->ID];

                    if ($menu[$menu_item->ID]->is_active || $menu[$menu_item->ID]->has_active_child) {
                        $menu[$menu_item->menu_item_parent]->has_active_child = true;
                    }

                    unset($menu[$menu_item->ID]);
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
}
