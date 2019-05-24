<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * Remove post Tag taxonomy.
 */
class DisableTags extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'init' => 'disableTags',
    ];

    /**
     * Remove post_tag taxonomy from all post types.
     */
    public function disableTags()
    {
        $types = \get_post_types();

        foreach ($types as $type) {
            \unregister_taxonomy_for_object_type('post_tag', $type);
        }
    }
}
