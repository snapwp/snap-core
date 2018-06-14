<?php

namespace Snap\Core\Modules;

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
     * @param array $allcaps All the capabilities of the user
     * @param array $cap     Required capability
     * @param array $args    {
     *     [0] Requested capability
     *     [1] User ID
     *     [2] Associated object ID
     * }
     * @return array $allcaps
     */
    public function shortcircuit_customize_cap($allcaps, $cap, $args)
    {
        if ($args[0] == 'customize') {
            $allcaps[ $cap[0] ] = false;
        }

        return $allcaps;
    }
}
