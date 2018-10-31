<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 *
 * @since  1.0.0
 */
class Disable_Customizer extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'user_has_cap' => 'shortcircuit_customize_cap',
    ];

    /**
     * The simplest and most thorough way to disable the customizer.
     *
     * @since  1.0.0
     *
     * @param array $all_caps All the capabilities of the user.
     * @param array $cap     Required capability.
     * @param array $args    {
     *     [0] Requested capability
     *     [1] User ID
     *     [2] Associated object ID
     * }
     * @return array $all_caps
     */
    public function shortcircuit_customize_cap($all_caps, $cap, $args)
    {
        if ($args[0] == 'customize') {
            $all_caps[ $cap[0] ] = false;
        }

        return $all_caps;
    }
}
