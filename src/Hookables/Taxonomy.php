<?php

namespace Snap\Hookables;

use Snap\Database\TaxQuery;
use Snap\Hookables\Content\ColumnController;
use Tightenco\Collect\Support\Arr;

/**
 * The Post Type Hookable.
 *
 * @method static \Tightenco\Collect\Support\Collection get()
 * @method static \Tightenco\Collect\Support\Collection|false|\WP_Term find(int|int[]|string|string[] $ids)
 * @method static array getNames()
 * @method static array getIds()
 * @method static array getSlugs()
 * @method static \WP_Term_Query getQueryObject()
 * @method static false|\WP_Term first()
 * @method static int count()
 * @method static TaxQuery for (int|\WP_Post|int[]|\WP_Post[] $object_ids)
 * @method static TaxQuery hideEmpty()
 * @method static TaxQuery includeEmpty()
 * @method static TaxQuery childOf(int|\WP_Term $parent)
 * @method static TaxQuery notChildOf(int|int[]|\WP_Term|\WP_Term[] $term_ids)
 * @method static TaxQuery directChildOf(int|\WP_Term $parent)
 * @method static TaxQuery childless()
 * @method static TaxQuery in(int|int[] $term_ids)
 * @method static TaxQuery exclude(int|int[] $term_ids)
 * @method static TaxQuery where(string|callable $key, $value, string $operator = '=', string $type = 'CHAR')
 * @method static TaxQuery orWhere(string|callable $key, $value, string $operator = '=', string $type = 'CHAR')
 * @method static TaxQuery whereExists(string $key)
 * @method static TaxQuery orWhereExists(string $key)
 * @method static TaxQuery whereNotExists(string $key)
 * @method static TaxQuery orWhereNotExists(string $key)
 * @method static TaxQuery whereName(string|string[] $names)
 * @method static TaxQuery whereNameLike(string $name)
 * @method static TaxQuery whereSlug(string|string[] $slugs)
 * @method static TaxQuery whereTaxonomyId(int|int[] $ids)
 * @method static TaxQuery whereLike(string $search)
 * @method static TaxQuery whereDescriptionLike(string $name)
 * @method static TaxQuery orderBy(string $order_by, string $order = 'ASC')
 * @method static TaxQuery limit(int $amount)
 * @method static TaxQuery offset(int $amount)
 */
class Taxonomy extends ContentHookable
{
    /**
     * Post types to attach to.
     */
    protected $post_types;

    /**
     * The content type.
     *
     * @var string
     */
    protected static $type = 'taxonomy';

    /**
     * Register the Taxonomy.
     */
    public function register()
    {
        if ($this->hasRegistered()) {
            return;
        }

        if (\taxonomy_exists($this->getName())) {
            $existing = \get_taxonomy($this->getName());

            if (empty($this->post_types)) {
                // Remove the taxonomy.
                $this->unRegisterTaxonomy($existing);
                return;
            }

            \register_taxonomy($this->getName(), null, $this->getOptionsForExisting($existing));
        } else {
            \register_taxonomy($this->getName(), null, $this->getOptions());
        }

        $this->registerAttachedPostTypes();
        $this->registerColumns();
    }

    /**
     * Add the taxonomy to the supplied post type.
     *
     * @param string|array $post_type Post type to register for.
     * @return $this
     */
    public function attachToPostType($post_type)
    {
        $post_type = Arr::wrap($post_type);

        foreach ($post_type as $type) {
            if (!isset(static::$relationships[$type])) {
                static::$relationships[$type] = [];
            }

            static::$relationships[$type] = \array_unique(
                \array_merge(
                    (array)static::$relationships[$type],
                    [$this->getName()]
                )
            );
        }

        return $this;
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

            $this->addFilter("manage_edit-{$this->getName()}_columns", [$columnController, 'manageColumns']);

            if (!empty($this->columns()->getCustomColumns())) {
                $this->addFilter(
                    "manage_{$this->getName()}_custom_column",
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

                $this->addFilter("parse_term_query", [$columnController, 'handleSortableColumns']);
            }
        }

        static::$registered_columns[] = static::class;
    }

    /**
     * Return a fresh PostQuery instance.
     *
     * @return \Snap\Database\TaxQuery
     */
    protected function makeNewQuery(): TaxQuery
    {
        return new TaxQuery($this->getName());
    }

    /**
     * Attach the taxonomy to any $post_types defined.
     */
    private function registerAttachedPostTypes()
    {
        if ($this->post_types !== null) {
            foreach ($this->post_types as $post_type) {
                $this->attachToPostType($post_type);
            }
        }
    }

    /**
     * Get the options to register the taxonomy with.
     *
     * @return array
     */
    private function getOptions(): array
    {
        $defaults = [
            'show_admin_column' => true,
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
     * Get the labels to register the taxonomy with.
     *
     * @return array
     */
    private function getLabels(): array
    {
        return [
            'name' => $this->getPlural(),
            'singular_name' => $this->getSingular(),
            'menu_name' => $this->getPlural(),
            'all_items' => \sprintf(__("All %s", 'theme'), $this->getPlural()),
            'edit_item' => \sprintf(__("Edit %s", 'theme'), $this->getPlural()),
            'view_item' => \sprintf(__("View %s", 'theme'), $this->getSingular()),
            'update_item' => \sprintf(__("Update %s", 'theme'), $this->getSingular()),
            'add_new_item' => \sprintf(__("Add New %s", 'theme'), $this->getSingular()),
            'new_item_name' => \sprintf(__("New %s Name", 'theme'), $this->getSingular()),
            'parent_item' => \sprintf(__("Parent %s", 'theme'), $this->getSingular()),
            'parent_item_colon' => \sprintf(__("Parent %s:", 'theme'), $this->getSingular()),
            'search_items' => \sprintf(__("Search %s", 'theme'), $this->getPlural()),
            'popular_items' => \sprintf(__("Popular %s", 'theme'), $this->getPlural()),
            'separate_items_with_commas' => \sprintf(__("Separate %s with commas", 'theme'), $this->getPlural()),
            'add_or_remove_items' => \sprintf(__("Add or remove %s", 'theme'), $this->getPlural()),
            'choose_from_most_used' => \sprintf(__("Choose from most used %s", 'theme'), $this->getPlural()),
            'not_found' => \sprintf(__("No %s found", 'theme'), $this->getPlural()),
        ];
    }

    /**
     * When overloading an existing taxonomy, we dont want to use any of the getOptions() defaults.
     *
     * @param object $existing Original taxonomy object.
     * @return array
     */
    private function getOptionsForExisting($existing): array
    {
        $new_args = \array_replace_recursive(\get_object_vars($existing), $this->options);
        $new_args['label'] = $this->getPlural();
        $new_args['labels'] = $this->getLabels();
        return $new_args;
    }

    /**
     * Removes a taxonomy.
     *
     * @param \WP_Taxonomy $taxonomy The taxonomy to unset.
     */
    private function unRegisterTaxonomy(\WP_Taxonomy $taxonomy)
    {
        foreach ($taxonomy->object_type as $type) {
            \unregister_taxonomy_for_object_type($this->getName(), $type);
            \do_action('unregistered_taxonomy_for_object_type', $taxonomy, $type);
        }

        $taxonomy->remove_rewrite_rules();
        $taxonomy->remove_hooks();

        // Only run this on front end requests.
        $this->addAction('template_redirect', function () {
            global $wp_taxonomies;
            unset($wp_taxonomies[$this->getName()]);
        });

        // Hide the post types on nav admin.
        $this->addAction('hidden_meta_boxes', function ($hidden) {
            global $wp_meta_boxes;
            if (isset($wp_meta_boxes['nav-menus']['side']['default']['add-' . $this->getName()])) {
                unset($wp_meta_boxes['nav-menus']['side']['default']['add-' . $this->getName()]);
            }
            return $hidden;
        });

        \do_action('unregistered_taxonomy', $taxonomy);
    }
}
