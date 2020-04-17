<?php

namespace Snap\Admin;

use Snap\Core\Hookable;
use Snap\Core\Snap;

/**
 * Enables the Snap admin theme.
 *
 * Based on Slate admin theme.
 *
 * @see   https://wordpress.org/plugins/slate-admin-theme/
 */
class Theme extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'menu_order' => 'movePageLinkAbovePosts',
        'custom_menu_order' => 'movePageLinkAbovePosts',
        'admin_enqueue_scripts' => 'enqueueSnapAdmin',
    ];

    /**
     * Remove the user color scheme picker to enforce Snap colors.
     */
    public function boot()
    {
        $this->removeAction('admin_color_scheme_picker', 'admin_color_scheme_picker');
    }

    /**
     * Move the page admin link above posts.
     *
     * @param  array $menu_order The current top level menu order.
     * @return array|bool
     */
    public function movePageLinkAbovePosts($menu_order)
    {
        if (!$menu_order) {
            return true;
        }

        $pages = \array_search('edit.php?post_type=page', $menu_order, true);

        if ($pages > 0) {
            $posts = \array_search('edit.php', $menu_order, true);

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
     */
    public function enqueueSnapAdmin()
    {
        \wp_enqueue_style(
            'snap_admin_theme',
            \get_theme_file_uri('vendor/snapwp/snap-core/assets/snap-theme.css'),
            [],
            Snap::VERSION
        );
    }
}
