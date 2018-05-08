<?php

namespace Snap\Core;

/**
 * A collection of useful helper functions.
 *
 * @since 1.0.0
 */
class Utils
{
    /**
     * Counts the number of widgets for a given sidebar ID.
     *
     * Useful for applying classes to a parent container.
     *
     * @since  1.0.0
     *
     * @param  string $sidebar_id The ID of the sidebar.
     * @return int The count of the widgets in the sidebar.
     */
    public static function get_widget_count($sidebar_id)
    {
        global $_wp_sidebars_widgets;
        
        // If not front page, the global is empty so set it another way.
        if (empty($_wp_sidebars_widgets)) {
            $_wp_sidebars_widgets = get_option('sidebars_widgets', []);
        }
        
        // if our sidebar exists, return count.
        if (isset($_wp_sidebars_widgets[ $sidebar_id ])) {
            return count($_wp_sidebars_widgets[ $sidebar_id ]);
        }
        
        return 0;
    }

    /**
     * Returns the translated role of the current user or for a given user object.
     *
     * @since  1.0.0
     *
     * @param  WP_User $user A user to get the role of
     * @return string|bool The translated name of the current role, false if no role found
     **/
    public static function get_user_role($user = null)
    {
        global $wp_roles;

        if (! $user) {
            $user = wp_get_current_user();
        }
        
        $roles = $user->roles;
        $role = array_shift($roles);
        
        return isset($wp_roles->role_names[ $role ]) ? translate_user_role($wp_roles->role_names[ $role ]) : false;
    }

    /**
     * Gets the current full URL of the page with query string, host, and scheme.
     *
     * @since  1.0.0
     *
     * @param  boolean  $remove_query If true, the URL is returned without any query params.
     * @return string   The current URL.
     */
    public static function get_current_url($remove_query = false)
    {
        global $wp;

        if ($remove_query === true) {
            return trailingslashit(home_url($wp->request));
        }

        return home_url(add_query_arg(null, null));
    }

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
        if (is_search() || is_404()) {
            return null;
        }
        
        switch ($post) {
            case null;
                global $post;
            case is_int($post):
                $post = get_post($post);
            case is_object($post):
                $ancestors = $post->ancestors;
                break;
            case is_array($post):
                $ancestors = $post['ancestors'];
                break;
        }
     
        if ($ancestors && ! empty($ancestors)) {
            return (int) end($ancestors);
        } else {
            return (int) $post->ID;
        }
    }

    /**
     * Get size information for all currently registered image sizes.
     *
     * @global $_wp_additional_image_sizes
     *
     * @since  1.0.0
     *
     * @return array Data for all currently registered image sizes.
     */
    public static function get_image_sizes()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $size) {
            if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                $sizes[ $size ] = [
                    'width' => get_option("{$size}_size_w"),
                    'height' => get_option("{$size}_size_h"),
                    'crop' => (bool) get_option("{$size}_crop"),
                ];
            } elseif (isset($_wp_additional_image_sizes[ $size ])) {
                $sizes[ $size ] = [
                    'width'  => $_wp_additional_image_sizes[ $size ]['width'],
                    'height' => $_wp_additional_image_sizes[ $size ]['height'],
                    'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
                ];
            }
        }

        return $sizes;
    }

    /**
     * Get size information for a specific image size.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|array Size data about an image size or false if the size doesn't exist.
     */
    public static function get_image_size($size)
    {
        $sizes = self::get_image_sizes();

        if (is_string($size) && isset($sizes[ $size ])) {
            return $sizes[ $size ];
        }

        return false;
    }

    /**
     * Get the width of a specific image size.
     *
     * Really useful for HTML img tags.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Width of an image size or false if the size doesn't exist.
     */
    public static function get_image_width($size)
    {
        // Get the size meta array.
        $size = self::get_image_size($size);

        if ($size === false) {
            return false;
        }

        if (isset($size['width'])) {
            return (int) $size['width'];
        }

        return false;
    }

    /**
     * Get the height of a specific image size.
     *
     * Really useful for HTML img tags.
     *
     * @since  1.0.0
     *
     * @param  string $size The image size for which to retrieve data.
     * @return bool|int Height of an image size or false if the size doesn't exist.
     */
    public static function get_image_height($size)
    {
        // Get the size meta array.
        $size = self::get_image_size($size);

        if ($size === false) {
            return false;
        }

        if (isset($size['height'])) {
            return (int) $size['height'];
        }

        return false;
    }

    /**
     * Get current page depth.
     *
     * @since  1.0.0
     *
     * @param int|WP_Post|null $page Optional. Post ID or post object. Defaults to the current queried object.
     * @return integer
     */
    public function get_page_depth($page = null)
    {
        if ($page === null) {
            global $wp_query;
        
            $object = $wp_query->get_queried_object();
        } else {
            $object = get_post($page);
        }

        $parent_id  = $object->post_parent;
        $depth = 0;

        while ($parent_id > 0) {
            $page = get_page($parent_id);
            $parent_id = $page->post_parent;
            $depth++;
        }
     
        return $depth;
    }

    /**
     * Lists debug info about all callbacks for a given hook.
     *
     * Returns information for all callbacks in order of execution and priority.
     *
     * @since  1.0.0
     *
     * @param  string $hook The hook to find callbacks for.
     * @return array        Array of priorities, each containing a nested array of callbacks.
     */
    final public static function debug_hook($hook = '')
    {
        global $wp_filter;

        $return = [];

        if (! is_string($hook) || ! isset($wp_filter[ $hook ]) || empty($wp_filter[ $hook ]->callbacks)) {
            return $return;
        }

        foreach ($wp_filter[ $hook ]->callbacks as $priority => $callbacks) {
            $return[ $priority ] = [];

            foreach ($callbacks as $key => $callback) {
                $function = $callback['function'];
                $args = $callback['accepted_args'];

                if (is_array($function)) {
                    // Is a class
                    if (is_callable([ $function[0], $function[1] ])) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Class Method',
                            new \ReflectionMethod($function[0], $function[1]),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                } elseif (is_object($function) && $function instanceof \Closure) {
                    // Is a closure.
                    $return[ $priority ][ $key ] = self::generate_hook_info(
                        'Closure',
                        new \ReflectionFunction($function),
                        $args
                    );
                } elseif (strpos($function, '::') !== false) {
                    // Is a static method.
                    list( $class, $method ) = explode('::', $function);

                    if (is_callable([ $class, $method ])) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Static Method',
                            new \ReflectionMethod($class, $method),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                } else {
                    // Is a function.
                    if (function_exists($function)) {
                        $return[ $priority ][ $key ] = self::generate_hook_info(
                            'Function',
                            new \ReflectionFunction($function),
                            $args
                        );
                    } else {
                        $return[ $priority ][ $key ] = self::generate_undefined_hook_info();
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Generate output for a hook callback.
     *
     * @since  1.0.0
     *
     * @param  string $type
     * @param  object $reflector The reflection object handling this callback.
     * @param  int    $args      The number of args defined when this callback was added.
     * @return array
     */
    final private static function generate_hook_info($type, $reflector, $args)
    {
        $output = [
            'type' => $type,
            'file_name' => $reflector->getFileName(),
            'line_number' => $reflector->getStartLine(),
            'class' => null,
            'name' => null,
            'is_internal' => false
        ];

        if ($reflector instanceof \ReflectionMethod) {
            $output['class'] = $reflector->getDeclaringClass()->getName();
        }

        if ('Closure' !== $type) {
            $output['name'] = $reflector->getName();
            $output['is_internal'] = $reflector->isInternal();
        }

        $output['accepted_args'] = $args;

        return $output;
    }

    /**
     * Generate output for an undefined callback.
     *
     * @since 1.0.0
     *
     * @return array
     */
    final private static function generate_undefined_hook_info()
    {
        return [
            'type' => 'Undefined',
            'file_name' => null,
            'line_number' => null,
            'class' => null,
            'name' => null,
            'is_internal' => null,
            'accepted_args' => 0,
        ];
    }
}
