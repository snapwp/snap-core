<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 */
class DisableCustomizer extends Hookable
{
    /**
     * Filters to add on init.
     *
     * @var array
     */
    protected $filters = [
        'user_has_cap' => 'shortcircuitCustomizeCap',
    ];

    /**
     * The simplest and most thorough way to disable the customizer.
     *
     * @param array $all_caps All the capabilities of the user.
     * @param array $cap      Required capability.
     * @param array $args     {
     *     [0] Requested capability
     *     [1] User ID
     *     [2] Associated object ID
     * }
     * @return array $all_caps
     */
    public function shortcircuitCustomizeCap($all_caps, $cap, $args): array
    {
        if ($args[0] === 'customize') {
            $all_caps[ $cap[0] ] = false;
        }

        return $all_caps;
    }
}
