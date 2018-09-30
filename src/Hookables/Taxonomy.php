<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use PostTypes\Taxonomy as Tax;

/**
 * Taxonomy base class.
 *
 * A wrapper around PostTypes\Taxonomy.
 *
 * @see  https://github.com/jjgrainger/PostTypes
 */
class Taxonomy extends Hookable
{
    /**
     * Override the Taxonomy name (defaults to snake case class name).
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
     * Override the Taxonomy labels.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_taxonomy#Arguments
     * @since  1.0.0
     * @var null|array
     */
    public $labels = [];

    /**
     * Override the Taxonomy options.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_taxonomy#Arguments
     * @since  1.0.0
     * @var null|array
     */
    public $options = [];
    
    /**
     * Register additional admin columns for this Taxonomy.
     *
     * @since  1.0.0
     * @var null|array
     */
    public $columns = [];

    /**
     * Attach post types by supplying the names to attach here.
     *
     * @since  1.0.0
     * @var array|string[]
     */
    public $posttypes = [];

    /**
     * Register the Taxonomy.
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        $taxonomy = new Tax($this->get_names(), $this->options, $this->labels);

        // Register any relationships.
        $this->add_relationships($taxonomy);

        // If the child class has defined columns.
        if (! empty($this->columns)) {
            $taxonomy->columns()->add($this->columns);

            foreach ($this->columns as $key => $title) {
                // If a getter has been set
                if (\is_callable([$this, "get_{$key}_column"])) {
                    $taxonomy->columns()->populate($key, [$this, "output_column"]);
                }

                // If a sort method has been defined, save the key in $sortable_columns.
                if (\is_callable([$this, "sort_{$key}_column"])) {
                    $this->sortable_columns[ $key ] = $key;
                }
            }
        }

        // Give the child class full access to the PostTypes\Taxonomy instance.
        $this->modify($taxonomy);

        // Register the Taxonomy.
        $taxonomy->register();

        // Register any sortable columns.
        $this->add_filter('manage_edit-' . $this->get_name() . '_sortable_columns', 'set_sortable_columns');

        // If there are sortable columns, run their callbacks.
        if (! empty($this->sortable_columns)) {
            $this->add_action('parse_term_query', 'sort_columns', 1, 999);
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
     * Runs any supplied sort_{$key}_column callbacks in parse_term_query.
     *
     * @since  1.0.0
     *
     * @param  WP_Query $query The global WP_Query object.
     */
    public function sort_columns($query)
    {
        // Bail if we are not on the correct admin page.
        if (! is_admin() || !\in_array($this->name, $query->query_vars['taxonomy'])) {
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
     * Register a columns sort method with PostTypes\Taxonomy.
     *
     * @since  1.0.0
     * @see  https://github.com/jjgrainger/PostTypes/ For all possible options.
     *
     * @param  string $content The content to return.
     * @param  string $column  The current column key.
     * @param  int    $term_id The current term ID.
     */
    public function output_column($content, $column, $term_id)
    {
        $method = "get_{$column}_column";

        return $this->{$method}($term_id);
    }

    /**
     * Allow the child class the ability to modify the Taxonomy instance directly.
     *
     * @since  1.0.0
     *
     * @param  PostTypes\Taxonomy $taxonomy The current Taxonomy instance.
     */
    protected function modify(Tax $taxonomy)
    {
    }

    /**
     * Define the taxonomy relationships, and whether each taxonomy can be quick filtered.
     *
     * @since  1.0.0
     *
     * @param PostTypes\Taxonomy $taxonomy The current Taxonomy instance.
     */
    private function add_relationships($taxonomy)
    {
        if (! empty($this->posttypes)) {
            foreach ($this->posttypes as $k => $v) {
                $taxonomy->posttype($v);
            }
        }
    }

    /**
     * Get the full array of overridden names to pass to PostTypes\Taxonomy.
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
     * Get the unqualified name of the current class and convert it to snake case for the taxonomy name.
     *
     * Can be overwritten by setting the $name property.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_name()
    {
        if ($this->name === null) {
            return $this->get_classname();
        }

        return $this->name;
    }
}
