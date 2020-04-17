<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use Snap\Database\Query;
use Snap\Hookables\Content\ColumnManager;
use Snap\Utils\Str;

abstract class ContentHookable extends Hookable
{
    /**
     * Array of Content Hookables that have registered their columns.
     *
     * @var array
     */
    protected static $registered_columns = [];

    /**
     * Whether the content is registered.
     *
     * @var array
     */
    protected static $has_registered = [
        'post' => [],
        'taxonomy' => [],
    ];

    /**
     * The content type.
     *
     * @var string
     */
    protected static $type;

    /**
     * Flag to indicate if taxonomies have been attached yet.
     *
     * @var bool
     */
    protected static $has_attached_taxonomies = false;

    /**
     * Map of scope methods.
     *
     * @var array
     */
    protected static $scope_cache = [];

    /**
     * Holds all relationships to be registered.
     *
     * @var array
     */
    protected static $relationships = [];

    /**
     * Holds all registered taxonomies with their plurals.
     *
     * @var array
     */
    protected static $taxonomy_plurals = [];

    /**
     * Override the name to register with (defaults to snake case class name).
     *
     * @var null|string
     */
    protected $name;

    /**
     * Front-facing singular name for the Content. For example Post, Page, Color, Category.
     * If not present, default to the capitalised class name.
     *
     * @var null|string
     */
    protected $singular;

    /**
     * Front-facing plural name for the Content. For example Posts, Pages, Colors, Categories.
     * If not present, default to a pluralized and capitalised class name.
     *
     * @var null|string
     */
    protected $plural;

    /**
     * Labels to register with.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Override the content options to register with.
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#Parameters
     * @var array
     */
    protected $options = [];

    /**
     * Shorthand setting of admin columns.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * ColumnManager instance.
     *
     * @var ColumnManager
     */
    protected $columnManager;

    /**
     * Register the content type.
     */
    abstract public function register(): void;

    /**
     * Register the admin columns.
     */
    abstract public function registerColumns(): void;

    /**
     * Should return a new Query builder object.
     *
     * @return \Snap\Database\Query
     */
    abstract protected function makeNewQuery();

    /**
     * Register columns.
     */
    public function __construct()
    {
        if (!empty($this->columns) && $this->hasRegisteredColumns() === false) {
            $this->columns()->add($this->columns);
        }

        $this->addFilter('init', 'register', 99);
        $this->addFilter('init', 'registerTaxonomiesForPosts', 100);
    }

    /**
     * Register all taxonomies defined in $relationships array.
     */
    public function registerTaxonomiesForPosts(): void
    {
        if (static::$has_attached_taxonomies === false) {
            foreach (static::$relationships as $post_type => $taxes) {
                foreach ($taxes as $tax) {
                    \register_taxonomy_for_object_type($tax, $post_type);
                }
            }
        }

        static::$has_attached_taxonomies = true;
    }

    /**
     * Forward all static calls to a new instance.
     *
     * @param string $name      Method name.
     * @param array  $arguments Method arguments.
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return (new static())->{$name}(...$arguments);
    }

    /**
     * Check for scope methods, else forward to a new PostQuery.
     *
     * @param string $name      Method name.
     * @param array  $arguments Method arguments.
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (\array_key_exists($name, static::$scope_cache)) {
            return $this->{static::$scope_cache[$name]}($this->makeNewQuery());
        }

        $scoped = 'scope' . \ucfirst($name);

        if (\method_exists($this, $scoped)) {
            static::$scope_cache[$name] = $scoped;
            return $this->{$scoped}($this->makeNewQuery(), ...$arguments);
        }

        return $this->makeNewQuery()->{$name}(...$arguments);
    }

    /**
     * Get ColumnManager instance.
     *
     * @return \Snap\Hookables\Content\ColumnManager
     */
    public function columns(): ColumnManager
    {
        if (isset($this->columnManager)) {
            return $this->columnManager;
        }

        $this->columnManager = new ColumnManager(static::$type);
        return $this->columnManager;
    }

    /**
     * Get the unqualified name of the current class and convert it to snake case for the post type name.
     *
     * Can be overwritten by setting the $name property.
     *
     * @return string
     */
    public function getName(): string
    {
        if ($this->name === null) {
            $this->name = $this->getClassname();
        }

        return $this->name;
    }

    /**
     * Get the plural name. Attempt some basic pluralisation if nor specified.
     *
     * @return string
     */
    public function getPlural(): string
    {
        if ($this->plural) {
            return __($this->plural, 'theme');
        }

        $this->plural = \ucwords(Str::toPlural($this->getSingular()));
        return __($this->plural, 'theme');
    }

    /**
     * Get the singular name.
     *
     * @return string
     */
    protected function getSingular(): string
    {
        if ($this->singular) {
            return __($this->singular, 'theme');
        }

        $this->singular = \ucwords(\str_replace('_', ' ', $this->getName()));
        return __($this->singular, 'theme');
    }

    /**
     * Whether the content has been registered into WordPress.
     *
     * @return bool
     */
    final protected function hasRegistered(): bool
    {
        if (\in_array(static::class, static::$has_registered[static::$type], true)) {
            return true;
        }

        if (static::$type === 'taxonomy') {
            static::$taxonomy_plurals[$this->getName()] = Str::toSnake($this->getPlural());
        }

        static::$has_registered[static::$type][$this->getName()] = static::class;
        return false;
    }

    /**
     * Whether there are registered columns.
     *
     * @return bool
     */
    final protected function hasRegisteredColumns(): bool
    {
        return \in_array(static::class, static::$registered_columns, true);
    }
}
