<?php

namespace Theme\Posts;

use Snap\Core\Post_Type;

/**
 * [Post_Type desciption]
 */
class CLASSNAME extends Post_Type
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
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @var null|array
     */
    public $options = [
        // 'public' => true,
        // 'hierarchical' => true,
        // 'has_archive' => true,
        // 'menu_icon' => null,
    ];

    /**
     * Attach Taxonomies by supplying the names to attach here.
     *
     * By default all taxonomies are added to the admin as filters for this post type.
     * By supplying name => false as a value for your taxonomy, it will not be added as a filter.
     *
     * @var array|string[]
     */
    public $taxonomies = [
        // 'example_taxonomy'
    ];
}
