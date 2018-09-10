<?php

namespace Snap\Admin;

use Snap\Core\Snap;
use Snap\Core\Hookable;

/**
 * Enables the Snap admin theme.
 *
 * Based on Slate admin theme.
 * <https://wordpress.org/plugins/slate-admin-theme/>
 *
 * @since  1.0.0
 */
class Theme extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'menu_order' => 'move_page_link_above_posts',
        'custom_menu_order' => 'move_page_link_above_posts',
        'admin_enqueue_scripts' => 'enqueue_snap_admin',
        'admin_menu' => [
            999 => 'clean_admin_menu',
        ],
    ];

    /**
     * Remove the user color scheme picker to enforce Snap colors.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        $this->remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
    }

    /**
     * Move the page admin link above posts.
     *
     * @since  1.0.0
     *
     * @param  array $menu_order The current top level menu order.
     * @return array
     */
    public function move_page_link_above_posts($menu_order)
    {
        if (! $menu_order) {
            return true;
        }
         
        $pages = \array_search('edit.php?post_type=page', $menu_order);

        if ($pages > 0) {
            $posts = \array_search('edit.php', $menu_order);

            if ($posts === false) {
                $posts = 2;
            }
            
            $pages = \array_splice($menu_order, $pages, 1);
            \array_splice($menu_order, $posts, 0, $pages);
        }

        return $menu_order;
    }

    /**
     * Enqueue the theme css.
     *
     * @since  1.0.0
     */
    public function enqueue_snap_admin()
    {
        \wp_enqueue_style(
            'snap_admin_theme',
            \get_theme_file_uri('vendor/snapwp/snap-core/src/Admin/assets/snap-admin.css'),
            [],
            Snap::VERSION
        );
    }

    /**
     * Remove the dashboard submenu.
     *
     * @since  1.0.0
     */
    public function clean_admin_menu()
    {
        \remove_submenu_page('index.php', 'index.php');
        \remove_submenu_page('index.php', 'update-core.php');
    }
}
