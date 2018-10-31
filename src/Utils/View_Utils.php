<?php

namespace Snap\Utils;

/**
 * Provides utilities for use within templates.
 *
 * @since 1.0.0
 */
class View_Utils
{
    /**
     * Get value of top level hierarchical post ID.
     *
     * Does not work with the objects returned by get_pages().
     *
     * @since  1.0.0
     *
     * @param (int|WP_Post|array) $post null Optional. Post object,array, or ID of a post to find the top ancestors for.
     * @return int ID
     */
    public static function get_top_level_parent_id($post = null)
    {
        if (\is_search() || \is_404()) {
            return null;
        }

        switch ($post) {
            // No post has been set, so use global.
            case null:
                global $post;

            // The post ID has been provided.
            case \is_int($post):
                $post = \get_post($post);

            // A WP_Post was provided.
            case \is_object($post):
                $ancestors = $post->ancestors;
                break;

            case \is_array($post):
                $ancestors = $post['ancestors'];
                break;
        }

        if (isset($ancestors) && ! empty($ancestors)) {
            return (int) \end($ancestors);
        } else {
            return (int) $post->ID;
        }
    }

    /**
     * Get current page depth.
     *
     * @since  1.0.0
     *
     * @param int|\WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
     * @return integer
     */
    public static function get_page_depth($page = null)
    {
        if ($page === null) {
            global $wp_query;

            $object = $wp_query->get_queried_object();
        } else {
            $object = \get_post($page);
        }

        $parent_id  = $object->post_parent;
        $depth = 0;

        while ($parent_id > 0) {
            $page = \get_post($parent_id);
            $parent_id = $page->post_parent;
            $depth++;
        }

        return $depth;
    }
}
