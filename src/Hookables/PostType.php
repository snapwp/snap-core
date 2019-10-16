<?php

namespace Snap\Hookables;

use Snap\Database\PostQuery;
use Snap\Hookables\Content\ColumnController;
use Tightenco\Collect\Support\Arr;
use Tightenco\Collect\Support\Collection;
use Snap\Utils\Str;

/**
 * Class PostType
 *
 * @method static false|\WP_Post first()
 * @method static Collection get()
 * @method static \WP_Query getWPQuery()
 * @method static Collection all()
 * @method static int count()
 * @method static false|\WP_Term|Collection find(string|string[]|int|int[] $search)
 *
 * @method static PostQuery withStatus(string|string[]|int|int[] $status)
 * @method static PostQuery withSticky()
 *
 * @method static PostQuery whereTaxonomy(string|callable $key, int|string|array $terms = '', string $operator = 'IN', bool $include_children = true)
 * @method static PostQuery orWhereTaxonomy(string|callable $key, int|string|array $terms = '', string $operator = 'IN', bool $include_children = true)
 * @method static PostQuery whereTerms($objects, string $operator = 'IN', bool $include_children = true)
 * @method static PostQuery orWhereTerms($objects, string $operator = 'IN', bool $include_children = true)
 *
 * @method static PostQuery whereAuthor(int|int[]|\WP_User|\WP_User[] $author)
 * @method static PostQuery whereAuthorNot(int|int[]|\WP_User|\WP_User[] $author)
 * @method static PostQuery whereLike(string $search)
 * @method static PostQuery whereExact(string $search)
 * @method static PostQuery whereSlug(string|string[] $slug)
 *
 * @method static PostQuery WhereDate(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery orWhereDate(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery whereDateBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static PostQuery orWhereDateBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static PostQuery whereDateNotBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static PostQuery orWhereDateNotBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static PostQuery whereDateBefore(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery orWhereDateBefore(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery whereDateAfter(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery orWhereDateAfter(\WP_Post|\DateTimeInterface|int $date)
 * @method static PostQuery whereYear(int $year, string $operator = '=')
 * @method static PostQuery orWhereYear(int $year, string $operator = '=')
 * @method static PostQuery whereMonth(int $month, string $operator = '=')
 * @method static PostQuery orWhereMonth(int $month, string $operator = '=')
 * @method static PostQuery whereDay(int $day, string $operator = '=')
 * @method static PostQuery orWhereDay(int $day, string $operator = '=')
 * @method static PostQuery whereHour(int $hour, string $operator = '=')
 * @method static PostQuery orWhereHour(int $hour, string $operator = '=')
 *
 * @method static PostQuery childOf(int|int[]|\WP_Post|\WP_Post[]$post_ids)
 * @method static PostQuery notChildOf(int|int[]|\WP_Post|\WP_Post[]$post_ids)
 * @method static PostQuery in(int|int[] $ids)
 * @method static PostQuery exclude(int|int[] $ids)
 *
 * @method static PostQuery orderBy(string $order_by, string $order = 'ASC')
 * @method static PostQuery limit(int $amount)
 * @method static PostQuery offset(int $amount)
 * @method static PostQuery page(int $page)
 */
class PostType extends ContentHookable
{
    /**
     * Taxonomies to register for the current post type.
     *
     * @var array
     */
    protected $taxonomies = [];

    /**
     * @inherit
     */
    protected static $type = 'post';

    /**
     * Whether accessors have been registered yet.
     *
     * @var bool
     */
    protected static $has_registered_accessors = false;

    /**
     * Registered admin filters.
     *
     * @var array
     */
    private $admin_filters = [];

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

    /**
     * Adds a filter to the admin for quick filtering by the supplied taxonomy.
     *
     * @param string|array $taxonomy Taxonomy or array of taxonomies to create a filter for.
     * @return $this
     */
    public function addTaxonomyFilter($taxonomy)
    {
        if (!\is_array($taxonomy)) {
            $this->admin_filters[] = $taxonomy;
            return $this;
        }

        $this->admin_filters = \array_merge($this->admin_filters, $taxonomy);
        return $this;
    }

    /**
     * Attach a taxonomy.
     *
     * @param string|array $taxonomy Taxonomy name to attach.
     * @return $this
     */
    public function attachTaxonomy($taxonomy)
    {
        if (!isset(static::$relationships[$this->getName()])) {
            static::$relationships[$this->getName()] = [];
        }

        $taxonomy = Arr::wrap($taxonomy);

        static::$relationships[$this->getName()] = \array_unique(
            \array_merge((array)static::$relationships[$this->getName()], $taxonomy)
        );

        return $this;
    }

    /**
     * Run any registered accessor methods.
     *
     * @param null   $default   Default return value.
     * @param int    $object_id The ID of the current post object.
     * @param string $meta_key  The key being looked up.
     * @param bool   $single    Whether to return only one result.
     * @return mixed
     */
    public function runAttributeAccessors($default, $object_id, $meta_key, $single)
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

        foreach (static::$has_registered[self::$type] as $post_type => $class) {
            if ($post->post_type !== $post_type) {
                continue;
            }

            // Return taxonomy Collection if the taxonomy exists.
            if (\in_array($meta_key, static::$taxonomy_plurals)) {
                $name = \array_search($meta_key, self::$taxonomy_plurals);

                if (\in_array($name, self::$relationships[$post_type])) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    return (new self::$has_registered['taxonomy'][$name])->for($object_id)->get();
                }
            }

            // Call accessor.
            if (\method_exists($class, $method)) {
                return (new $class)->{$method}($post);
            }
        }

        return $default;
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
            'all_items' => \sprintf(__("All %s"), $this->getPlural()),
            'add_new' => \sprintf(__("Add New %s"), $this->getSingular()),
            'add_new_item' => \sprintf(__("Add New %s"), $this->getSingular()),
            'edit_item' => \sprintf(__("Edit %s"), $this->getSingular()),
            'new_item' => \sprintf(__("New %s"), $this->getSingular()),
            'view_item' => \sprintf(__("View %s"), $this->getSingular()),
            'search_items' => \sprintf(__("Search %s"), $this->getPlural()),
            'not_found' => \sprintf(__("No %s found"), $this->getPlural()),
            'not_found_in_trash' => \sprintf(__("No %s found in Trash"), $this->getPlural()),
            'parent_item_colon' => \sprintf(__("Parent %s:"), $this->getSingular()),
        ];
    }

    /**
     * Add taxonomies based on the taxonomies property.
     */
    private function registerAttachedTaxonomies()
    {
        if ($this->taxonomies !== null) {
            foreach ($this->taxonomies as $taxonomy) {
                $this->attachTaxonomy($taxonomy);
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
        // todo unset taxonomies
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
            $this->addFilter('get_post_metadata', 'runAttributeAccessors', 10, 4);
            static::$has_registered_accessors = true;
        }
    }

    private function registerFilters(): void
    {
        $this->addFilter(
            'restrict_manage_posts',
            function (string $post_type) {
                if ($post_type === $this->getName()) {
                    foreach ($this->admin_filters as $filter) {
                        $this->outputTaxonomyFilter($filter);
                    }
                }
            }
        );
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

        $terms = \get_terms(
            [
                'taxonomy' => $taxonomy,
                'orderby' => 'name',
                'hide_empty' => false,
            ]
        );

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
