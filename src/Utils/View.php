<?php

namespace Snap\Utils;

/**
 * Provides utilities for use within templates.
 */
class View
{
    /**
     * Get value of top level hierarchical post ID.
     *
     * Does not work with the objects returned by get_pages().
     *
     * @param int|\WP_Post|array $post Optional. Post object, array, or ID of a post to find the top ancestors for.
     * @return int|null
     */
    public static function getTopLevelParentId($post = null): ?int
    {
        if (\is_search() || \is_404()) {
            return null;
        }

        switch ($post) {
            // No post has been set, so use global.
            case null:
                global $post;
                $ancestors = $post->ancestors;
                break;

            // The post ID has been provided.
            case \is_int($post):
                $post = \get_post($post);
                $ancestors = $post->ancestors;
                break;

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
     * @param int|\WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
     * @return integer
     */
    public static function getPageDepth($page = null): int
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
