<?php

namespace Snap\Utils;

/**
 * Sidebar and widget utilities.
 */
class Sidebar
{
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * Useful for applying classes to a parent container.
     *
     * @param  string $sidebar_id The ID of the sidebar.
     * @return int The count of the widgets in the sidebar.
     */
    public static function getWidgetCount(string $sidebar_id): int
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
