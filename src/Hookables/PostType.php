<?php

namespace Snap\Hookables;

use PostTypes\PostType as PT;
use Snap\Core\Hookable;

/**
 * Post type base class.
 *
 * A wrapper around \PostTypes\PostType.
 *
 * @see   https://github.com/jjgrainger/PostTypes
 * @since 1.0.0
 */
class PostType extends Hookable
{
    /**
     * Override the post type name (defaults to snake case class name).
     *
     * @var null|string
     */
    public $name = null;

    /**
     * Override the plural name. Defaults to {$name}s.
     *
     * @var null|string
     */
    public $plural = null;

    /**
     * Override the plural name. Defaults to $name.
     *
     * @var null|string
     */
    public $singular = null;

    /**
     * Override the plural name. Defaults to kebab cased $name.
     *
     * @var null|string
     */
    public $slug = null;

    /**
     * Override the post type labels.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @var null|array
     */
    public $labels = [];

    /**
     * Override the post type options.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @var null|array
     */
    public $options = [];

    /**
     * Register additional admin columns for this post type.
     *
     * @var null|array
     */
    public $columns = [];

    /**
     * Register which columns should be sortable for post type.
     *
     * @var null|array
     */
    public $sortable_columns = [];

    /**
     * Attach Taxonomies by supplying the names to attach here.
     *
     * By default all taxonomies are added to the admin as filters for this post type.
     * By supplying name => false as a value for your taxonomy, it will not be added as a filter.
     *
     * @var array|string[]
     */
    public $taxonomies = [];

    /**
     * Register the post type.
     */
    public function __construct()
    {
        $post_type = new PT($this->getNames(), $this->options, $this->labels);

        // Register any relationships.
        $this->addRelationships($post_type);

        // If the child class has defined columns.
        if (!empty($this->columns)) {
            $post_type->columns()->add($this->columns);

            foreach ($this->columns as $key => $title) {
                // If a getter has been set.
                if (\is_callable([$this, "get_{$key}_column"])) {
                    $post_type->columns()->populate($key, [$this, "outputColumn"]);
                }

                // If a sort method has been defined, save the key in $sortable_columns.
                if (\is_callable([$this, "sort_{$key}_column"])) {
                    $this->sortable_columns[$key] = $key;
                }
            }
        }

        // Give the child class full access to the PostTypes\PostType instance.
        $this->modify($post_type);

        // Register the post type.
        $post_type->register();

        // Register any sortable columns.
        $this->addFilter('manage_edit-' . $this->getName() . '_sortable_columns', 'setSortableColumns');

        // If there are sortable columns, run their callbacks.
        if (!empty($this->sortable_columns)) {
            $this->addAction('pre_get_posts', 'sortColumns', 1, 999);
        }
    }

    /**
     * Make custom columns sortable.
     *
     * @param array $columns Default WordPress sortable columns.
     * @return array
     */
    public function setSortableColumns($columns)
    {
        if (!empty($this->sortable_columns)) {
            $columns = \array_merge($columns, $this->sortable_columns);
        }

        return $columns;
    }

    /**
     * Runs any supplied sort_{$key}_column callbacks in pre_get_posts.
     *
     * @param  \WP_Query $query The global WP_Query object.
     */
    public function sortColumns($query)
    {
        // Bail if we are not on the correct admin page.
        if (!$query->is_main_query() || !\is_admin() || $query->get('post_type') !== $this->getName()) {
            return;
        }

        $order_by = $query->get('orderby');

        // Check if the current sorted column has a sort callback defined.
        if (isset($this->sortable_columns[$order_by])) {
            $callback = "sort_{$order_by}_column";
            $this->{$callback}($query);
        }
    }

    /**
     * Register a columns sort method with PostTypes\PostType.
     *
     * @see    https://github.com/jjgrainger/PostTypes/ For all possible options.
     *
     * @param  string $column  The current column key.
     * @param  int    $post_id The current post ID.
     */
    public function outputColumn($column, $post_id)
    {
        $method = "get_{$column}_column";

        $this->{$method}($post_id);
    }

    /**
     * Allow the child class the ability to modify the PostType instance directly.
     *
     * @param \PostTypes\PostType $post_type The current PostType instance.
     */
    protected function modify(PT $post_type)
    {
    }

    /**
     * Define the taxonomy relationships, and whether each taxonomy can be quick filtered.
     *
     * @param \PostTypes\PostType $post_type The current PostType instance.
     */
    private function addRelationships(PT $post_type)
    {
        if (!empty($this->taxonomies)) {
            $filters = [];

            foreach ($this->taxonomies as $k => $v) {
                // Add all taxonomies to $filters unless explicitly declared otherwise.
                if (\is_integer($k)) {
                    $post_type->taxonomy($v);
                    $filters[] = $v;
                    continue;
                }

                if ($v === false) {
                    $post_type->taxonomy($k);
                }
            }

            $post_type->filters($filters);
        }
    }

    /**
     * Get the full array of overridden names to pass to PostTypes\PostType.
     *
     * @return array All names.
     */
    private function getNames()
    {
        $names = [
            'name' => $this->getName(),
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
     * Get the unqualified name of the current class and convert it to snake case for the post type name.
     *
     * Can be overwritten by setting the $name property.
     *
     * @return string
     */
    private function getName()
    {
        if ($this->name === null) {
            return $this->getClassname();
        }

        return $this->name;
    }
}
