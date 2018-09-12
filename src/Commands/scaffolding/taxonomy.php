<?php

namespace Theme\Taxonomies;

use Snap\Core\Taxonomy;

/**
 * [Taxonomy desciption]
 */
class CLASSNAME extends Taxonomy
{
    /**
     * Override the plural name.
     *
     * @var null|string
     */
    public $plural = 'CLASSNAMEs';

    /**
     * Override the post type default options.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_taxonomy#Arguments
     * @var null|array
     */
    public $options = [
        // 'public' => true,
        // 'hierarchical' => true,
    ];
}
