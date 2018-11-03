<?php

namespace Snap\Utils;

/**
 * Sidebar and widget utilities.
 *
 * @since 1.0.0
 */
class Sidebar_Utils
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
            $_wp_sidebars_widgets = \get_option('sidebars_widgets', []);
        }

        // if our sidebar exists, return count.
        if (isset($_wp_sidebars_widgets[ $sidebar_id ])) {
            return \count($_wp_sidebars_widgets[ $sidebar_id ]);
        }

        return 0;
    }
}
