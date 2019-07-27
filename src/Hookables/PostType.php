<?php

namespace Snap\Hookables;

use Snap\Hookables\Content\ColumnController;
use Snap\Database\PostQuery;
use Snap\Utils\Str;


class PostType extends ContentHookable
{
    protected $taxonomies = [];

    protected static $type = 'post';

    protected static $has_registered_accessors = false;
    protected static $post_types = [];

    private $admin_filters = [];

    /**
     * Run any registered accessor methods.
     *
     * @param null   $default   Default return value.
     * @param int    $object_id The ID of the current post object.
     * @param string $meta_key  The key being looked up.
     * @param bool   $single    Whether to return only one result.
     * @return mixed
     */
    public static function runAttributeAccessors($default, $object_id, $meta_key, $single)
    {
        // Do not run for built-ins.
        if ($meta_key === '' || $meta_key[0] === '_' || $single === false) {
            return $default;
        }

        $method = 'get' . Str::toStudly($meta_key) . 'Attribute';

        // As we are getting post meta, we know it will be loaded in the cache already - so this won't be expensive.
        $post = \get_post($object_id);

        if ($post === null) {
            return $default;
        }

        foreach (static::$hasRegistered[self::$type] as $post_type => $class) {
            if ($post->post_type !== $post_type) {
                continue;
            }

            if (\method_exists($class, $method)) {
                return $class::{$method}($post);
            }
        }

        return $default;
    }

    /**
     * Register the post type.
     */
    public function register()
    {
        if ($this->hasRegistered()) {
            return;
        }

        if (\post_type_exists($this->getName())) {
            $this->registerExistingPostType();
        } else {
            $this->registerPostType();
        }

        $this->registerAttachedTaxonomies();
        $this->registerFilters();

        $this->registerAccessors();
        $this->registerColumns();
    }

    /**
     * Register any column hooks.
     */
    public function registerColumns()
    {
        if ($this->hasRegisteredColumns() === true) {
            return;
        }

        if (isset($this->columnManager)) {
            $columnController = new ColumnController($this, $this->columnManager);

            $this->addFilter("manage_{$this->getName()}_posts_columns", [$columnController, 'manageColumns']);

            if (!empty($this->columns()->getCustomColumns())) {
                $this->addFilter(
                    "manage_{$this->getName()}_posts_custom_column",
                    [$columnController, 'handleColumnOutput'],
                    10,
                    3
                );
            }

            if (!empty($this->columns()->getSortableColumns())) {
                $this->addFilter(
                    "manage_edit-{$this->getName()}_sortable_columns",
                    [$columnController, 'setSortableColumns']
                );

                $this->addFilter("pre_get_posts", [$columnController, 'handleSortableColumns']);
            }
        }

        static::$registered_columns[] = static::class;
    }


    public function filter($filter)
    {
        if (!\is_array($filter)) {
            $this->admin_filters[] = $filter;
            return $this;
        }

        $this->admin_filters = \array_merge($this->admin_filters, $filter);
        return $this;
    }

    public function addTaxonomy(string $taxonomy)
    {
        if (!isset(static::$relationships[$this->getName()])) {
            static::$relationships[$this->getName()] = [];
        }

        static::$relationships[$this->getName()] = \array_unique(
            \array_merge((array)static::$relationships[$this->getName()], [$taxonomy])
        );

        return $this;
    }

    /**
     * Return a fresh PostQuery instance.
     *
     * @return \Snap\Database\PostQuery
     */
    protected function makeNewQuery()
    {
        return new PostQuery($this->getName());
    }

    /**
     * Get the options to register the post type with.
     *
     * @return array
     */
    private function getOptions(): array
    {
        $defaults = [
            'public' => true,
            'rewrite' => [
                'slug' => $this->getName(),
                'with_front' => false,
            ],
        ];

        $options = \array_replace_recursive($defaults, $this->options);

        if (!isset($options['labels'])) {
            $options['labels'] = $this->getLabels();
        }

        return $options;
    }

    /**
     * Get the labels to register the post type with.
     *
     * @return array
     */
    private function getLabels(): array
    {
        return [
            'name' => $this->getPlural(),
            'singular_name' => $this->getSingular(),
            'menu_name' => $this->getPlural(),
            'all_items' => sprintf(__("All %s"), $this->getSingular()),
            'add_new' => sprintf(__("Add New %s"), $this->getSingular()),
            'add_new_item' => sprintf(__("Add New %s"), $this->getSingular()),
            'edit_item' => sprintf(__("Edit %s"), $this->getSingular()),
            'new_item' => sprintf(__("New %s"), $this->getSingular()),
            'view_item' => sprintf(__("View %s"), $this->getSingular()),
            'search_items' => sprintf(__("Search %s"), $this->getSingular()),
            'not_found' => sprintf(__("No %s found"), $this->getSingular()),
            'not_found_in_trash' => sprintf(__("No %s found in Trash"), $this->getSingular()),
            'parent_item_colon' => sprintf(__("Parent %s:"), $this->getSingular()),
        ];
    }

    /**
     * Add taxonomies based on the taxonomies property.
     */
    private function registerAttachedTaxonomies()
    {
        if ($this->taxonomies !== null) {
            foreach ($this->taxonomies as $taxonomy) {
                $this->addTaxonomy($taxonomy);
            }
        }
    }

    /**
     * Override existing post type.
     */
    private function registerExistingPostType()
    {
        $existing = \get_post_type_object($this->getName());
        $new_args = \array_replace_recursive(\get_object_vars($existing), $this->getOptions());
        $new_args['label'] = $this->getPlural();
        \register_post_type($this->getName(), $new_args);
    }

    /**
     * Register the post type.
     */
    private function registerPostType()
    {
        \register_post_type($this->getName(), $this->getOptions());
    }

    /**
     * Add hook to allow accessor methods.
     */
    private function registerAccessors()
    {
        if (static::$has_registered_accessors === false) {
            $this->addFilter('get_post_metadata', 'self::runAttributeAccessors', 10, 4);
            static::$has_registered_accessors = true;
        }
    }

    private function registerFilters(): void
    {
        $this->addFilter('restrict_manage_posts', function (string $post_type) {
            if ($post_type === $this->getName()) {
                foreach ($this->admin_filters as $filter) {
                    if (\is_callable($filter)) {
                        dump('closure');
                        continue;
                    }

                    $this->outputTaxonomyFilter($filter);
                }
            }
        });
    }

    /**
     * Outputs a taxonomy dropdown filter.
     *
     * @param string $taxonomy The taxonomy name.
     */
    private function outputTaxonomyFilter(string $taxonomy)
    {
        if (!\taxonomy_exists($taxonomy)) {
            return;
        }

        $tax = \get_taxonomy($taxonomy);

        $terms = \get_terms([
            'taxonomy' => $taxonomy,
            'orderby' => 'name',
            'hide_empty' => false,
        ]);

        if (empty($terms)) {
            return;
        }

        $selected = null;

        if (isset($_GET[$taxonomy])) {
            $selected = \sanitize_title($_GET[$taxonomy]);
        }

        $dropdown_args = [
            'option_none_value' => '',
            'hide_empty' => 0,
            'hide_if_empty' => false,
            'show_count' => true,
            'taxonomy' => $tax->name,
            'name' => $taxonomy,
            'orderby' => 'name',
            'hierarchical' => true,
            'show_option_none' => "Show all {$tax->label}",
            'value_field' => 'slug',
            'selected' => $selected,
        ];

        \wp_dropdown_categories($dropdown_args);
    }
}