<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * Remove post Tag taxonomy.
 *
 * @since  1.0.0
 */
class Disable_Tags extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @since  1.0.0
     * @var array
     */
    protected $actions = [
        'init' => 'disable_tags',
    ];

    /**
     * Remove post_tag taxonomy from all post types.
     *
     * @since 1.0.0
     */
    public function disable_tags()
    {
        $types = \get_post_types();

        foreach ($types as $type) {
            \unregister_taxonomy_for_object_type('post_tag', $type);
        }
    }
}
