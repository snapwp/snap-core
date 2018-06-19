<?php

namespace Snap\Core;

use PostTypes\PostType;

class Post_Type extends Hookable
{
    /**
     * Override the post type name (defaults to snake case class name).
     *
     * @since  1.0.0
     * @var null|string
     */
    public $name = null;

    /**
     * Override the plural name. Defaults to {$name}s.
     *
     * @since  1.0.0
     * @var null|string
     */
    public $plural = null;

    /**
     * Override the plural name. Defaults to $name.
     *
     * @since  1.0.0
     * @var null|string
     */
    public $singular = null;
    
    /**
     * Override the plural name. Defaults to kebab cased $name.
     *
     * @since  1.0.0
     * @var null|string
     */
    public $slug = null;

    /**
     * Override the post type labels.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @since  1.0.0
     * @var null|array
     */
    public $labels = [];

    /**
     * Override the post type options.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @since  1.0.0
     * @var null|array
     */
    public $options = [];
    
    public $columns = [];

    /**
     * [$taxonomies description]
     * @var array
     */
    public $taxonomies = [];


    public function __construct()
    {
        $post_type = new PostType($this->get_names(), $this->options, $this->labels);

        // Register any relationships.
        $this->add_relationships($post_type);

        // If the child class has defined columns.
        if (! empty($this->columns)) {
            $post_type->columns()->add($this->columns);

            foreach ($this->columns as $key => $title) {
                // If a getter has been set
                if (\is_callable([$this, "get_{$key}_column"])) {
                    $post_type->columns()->populate($key, [$this, "output_column"]);
                }

                // If a sort method has been defined, save the key in $sortable_columns.
                if (\is_callable([$this, "sort_{$key}_column"])) {
                    $this->sortable_columns[ $key ] = $key;
                }
            }
        }

        // Give the child class full access to the PostTypes\PostType instance.
        $this->modify($post_type);

        // Register the post type.
        $post_type->register();

        // Register any sortable columns.
        $this->add_filter('manage_edit-' . $this->get_name() . '_sortable_columns', 'set_sortable_columns');

        // If there are sortable columns, run their callbacks.
        if (! empty($this->sortable_columns)) {
            $this->add_action('pre_get_posts', 'sort_columns', 1, 999);
        }
    }

    /**
     * Make custom columns sortable.
     *
     * @since 1.0.0
     *
     * @param array  $columns  Default WordPress sortable columns.
     */
    public function set_sortable_columns($columns)
    {
        if (! empty($this->sortable_columns)) {
            $columns = \array_merge($columns, $this->sortable_columns);
        }

        return $columns;
    }

    /**
     * Runs any supplied sort_{$key}_column callbacks in pre_get_posts.
     *
     * @since  1.0.0
     *
     * @param  WP_Query $query The global WP_Query object.
     */
    public function sort_columns($query)
    {
        // Bail if we are not on the correct admin page.
        if (! $query->is_main_query() || ! is_admin() || $query->get('post_type') !== $this->get_name()) {
            return;
        }

        $order_by = $query->get('orderby');

        // Check if the current sorted column has a sort callback defined.
        if (isset($this->sortable_columns[ $orderby ])) {
            $callback = "sort_{$order_by}_column";
            $this->{$callback}($query);
        }
    }

    /**
     * Register a columns sort method with PostTypes\PostType.
     *
     * @since  1.0.0
     * @see  https://github.com/jjgrainger/PostTypes/ For all possible options.
     *
     * @param  string $column  The current column key.
     * @param  int    $post_id The current post ID.
     */
    public function output_column($column, $post_id)
    {
        $method = "get_{$column}_column";

        $this->{$method}($post_id);
    }

    /**
     * Allow the child class the ability to modify the PostType instance directly.
     *
     * @since  1.0.0
     *
     * @param  PostTypes\PostType $post_type The current PostType instance.
     */
    protected function modify(PostType $post_type)
    {
    }

    private function add_relationships($post_type)
    {
        if (! empty($this->taxonomies)) {
            foreach ($this->taxonomies as $tax) {
                $post_type->taxonomy($tax);
            }







            // TODO
            $post_type->filters($this->taxonomies);
        }
    }

    /**
     * Get the full array of overridden names to pass to PostTypes\PostType.
     *
     * @since 1.0.0
     *
     * @return array All names.
     */
    private function get_names()
    {
        $names = [
            'name' => $this->get_name(),
        ];

        if ($this->plural !== null) {
            $names['plural'] = $this->plural;
        }

        if ($this->singular !== null) {
            $names['singular'] = $this->singular;
        }

        if ($this->slug !== null) {
            $names['slug'] = $this->slug;
        }

        return $names;
    }

    /**
     * Get the unqualified name of the current class and convert it to snake case for the shortcode name.
     *
     * Can be overwritten by setting the $tag property.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_name()
    {
        if ($this->name === null) {
            $classname = \basename(\str_replace(['\\', '_'], ['/', ''], \get_class($this)));
            $classname = \trim(\preg_replace('/([^_])(?=[A-Z])/', '$1_', $classname), '_');

            return \strtolower($classname);
        }

        return $this->name;
    }
}
