<?php

namespace Snap\Database;

use Snap\Database\Concerns\QueriesDate;
use Snap\Database\Concerns\QueriesMeta;
use Tightenco\Collect\Support\Arr;
use Tightenco\Collect\Support\Collection;
use WP_Post;
use WP_Query;
use WP_User;

class PostQuery extends Query
{
    use QueriesDate, QueriesMeta;

    private $tax_query = [];

    /**
     * PostQuery constructor.
     *
     * @param string|array $type The post type to query.
     */
    public function __construct(string $type = 'post')
    {
        parent::__construct($type);
        $this->params['ignore_sticky_posts'] = true;
    }

    /**
     * Change the queried post type.
     *
     * @param string $type
     * @return $this
     */
    public function type(string $type = 'post'): PostQuery
    {
        $this->name = $type;
        return $this;
    }

    /**
     * Returns the first found WP_Post.
     *
     * @return null|WP_Post
     */
    public function first(): ?WP_Post
    {
        return $this->getPost(
            $this->createArguments(
                [
                    'post_type' => $this->name,
                    'posts_per_page' => 1,
                    'no_found_rows' => true,
                ]
            )
        );
    }

    /**
     * Return found WP_Posts.
     *
     * @return \Tightenco\Collect\Support\Collection;
     */
    public function get(): Collection
    {
        return $this->getCollection(
            $this->createArguments(
                [
                    'post_type' => $this->name,
                    'no_found_rows' => true,
                ]
            )
        );
    }

    /**
     * Return a standard WP_Query object for looping through.
     *
     * @return \WP_Query
     */
    public function getWPQuery(): WP_Query
    {
        return new WP_Query(
            $this->createArguments(
                [
                    'post_type' => $this->name,
                ]
            )
        );
    }

    /**
     * Return all found WP_Posts with no pagination, ignoring any additional arguments.
     *
     * @return \Tightenco\Collect\Support\Collection;
     */
    public function all(): Collection
    {
        return $this->getCollection(
            $this->createArguments(
                [
                    'post_type' => $this->name,
                    'posts_per_page' => -1,
                    'no_found_rows' => true,
                ]
            )
        );
    }

    /**
     * Returns a count of all published posts for the current post type(s).
     *
     * @return int
     */
    public function count(): int
    {
        if (\is_array($this->name)) {
            $total = 0;

            foreach ($this->name as $type) {
                $total += (int)\wp_count_posts($type)->publish;
            }

            return $total;
        }

        return (int)\wp_count_posts($this->name)->publish;
    }

    /**
     * Lookup Posts by slugs or IDs.
     *
     * @param string|string[]|int|int[] $search
     * @return false|WP_Post|\Tightenco\Collect\Support\Collection;
     */
    public function find($search)
    {
        if (\is_array($search)) {
            return $this->findMultiple($search);
        }

        return $this->findSingle($search);
    }

    /**
     * Only return posts which match the provided status(es).
     *
     * @param string|string[] $status Default statuses are publish, pending, draft, auto-draft, future, private,
     *                                inherit, trash, any.
     * @return $this
     */
    public function withStatus($status): PostQuery
    {
        $this->params['post_status'] = $status;
        return $this;
    }

    /**
     * Include sticky posts in the results.
     */
    public function withSticky(): PostQuery
    {
        $this->params['ignore_sticky_posts'] = false;
        return $this;
    }

    /**
     * Add a tax query.
     *
     * @param string|callable $key Taxonomy, or Callable for nested queries.
     * @param int|string|array $terms Taxonomy term(s).
     * @param string $operator Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS'
     *                                           and 'NOT EXISTS'.
     * @param bool $include_children Whether or not to include children for hierarchical taxonomies.
     *                                           Defaults to true.
     * @return $this
     */
    public function whereTaxonomy($key, $terms = '', string $operator = 'IN', bool $include_children = true): PostQuery
    {
        if (\is_callable($key)) {
            $child_query = new static($this->name);
            \call_user_func($key, $child_query);
            $this->tax_query[] = $child_query->getTaxQuery();
            return $this;
        }

        $this->tax_query[] = $this->generateTaxQueryArgs($key, $terms, $operator, $include_children);
        return $this;
    }

    /**
     * Add a tax query, and make the current tax query an OR relation.
     *
     * @param string|callable $key Taxonomy, or Callable for nested queries.
     * @param int|string|array $terms Taxonomy term(s).
     * @param string $operator Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS'
     *                                           and 'NOT EXISTS'.
     * @param bool $include_children Whether or not to include children for hierarchical taxonomies.
     *                                           Defaults to true.
     * @return $this
     */
    public function orWhereTaxonomy($key, $terms = '', string $operator = 'IN', bool $include_children = true): PostQuery
    {
        $this->tax_query = ['relation' => 'OR'] + $this->tax_query;
        $this->whereTaxonomy($key, $terms, $operator, $include_children);
        return $this;
    }

    /**
     * Shorthand for whereTaxonomy that allows working with WP_Term instances directly.
     *
     * @param \WP_Term|\WP_Term[]|Collection $objects Wp_Term(s) to query for.
     * @param string $operator Operator to test. Possible values are 'IN', 'NOT IN',
     *                                                         'AND',
     *                                                         'EXISTS' and 'NOT EXISTS'.
     * @param bool $include_children Whether or not to include children for hierarchical
     *                                                         taxonomies.
     * @return $this
     */
    public function whereTerms($objects, string $operator = 'IN', bool $include_children = true): PostQuery
    {
        if (\is_array($objects) || $objects instanceof Collection) {
            $objects = collect($objects);
            $first = $objects->first();

            if ($first instanceof \WP_Term) {
                return $this->whereTaxonomy(
                    $first->taxonomy,
                    $objects->pluck('term_id')->all(),
                    $operator,
                    $include_children
                );
            }
        }

        if ($objects instanceof \WP_Term) {
            $key = $objects->taxonomy;
            $terms = $objects->term_id;
        }

        if (!isset($key)) {
            throw new \BadMethodCallException('PostQuery::term() expects a WP_Term or array of WP_Terms.');
        }

        return $this->whereTaxonomy($key, $terms, $operator, $include_children);
    }

    /**
     * Call whereTerms() as an OR relation.
     *
     * @param \WP_Term|\WP_Term[]|Collection $objects Wp_Term(s) to query for.
     * @param string $operator Operator to test. Possible values are 'IN', 'NOT IN',
     *                                                         'AND',
     *                                                         'EXISTS' and 'NOT EXISTS'.
     * @param bool $include_children Whether or not to include children for hierarchical
     *                                                         taxonomies.
     * @return $this
     */
    public function orWhereTerms($objects, string $operator = 'IN', bool $include_children = true): PostQuery
    {
        $this->tax_query = ['relation' => 'OR'] + $this->tax_query;
        return $this->whereTerms($objects, $operator, $include_children);
    }

    /**
     * Limit results to provided authors.
     *
     * @param int|int[]|\WP_User|\WP_User[] $author Author(s) to limit results to.
     *
     * @return $this
     */
    public function whereAuthor($author): PostQuery
    {
        if ($author instanceof WP_User) {
            $this->params['author'] = $this->getIdFromWpUser($author);
            return $this;
        }

        if (\is_array($author)) {
            foreach ($author as $key => $value) {
                if ($value instanceof WP_User) {
                    $author[$key] = $this->getIdFromWpUser($value);
                } else {
                    $author[$key] = (int)$value;
                }
            }

            $this->params['author__in'] = $author;
            return $this;
        }

        $this->params['author'] = (int)$author;
        return $this;
    }

    /**
     * Limit results posts not by the provided authors.
     *
     * @param int|int[]|\WP_User|\WP_User[] $author Author(s) to omit results from.
     *
     * @return $this
     */
    public function whereAuthorNot($author): PostQuery
    {
        $author = Arr::wrap($author);

        foreach ($author as $key => $value) {
            if ($value instanceof WP_User) {
                $author[$key] = $this->getIdFromWpUser($value);
            } else {
                $author[$key] = (int)$value;
            }
        }

        $this->params['author__not_in'] = $author;
        return $this;
    }

    /**
     * Perform a fuzzy search for a term in the post title, excerpt, and content.
     *
     * @param string $search String to search for.
     * @return $this
     */
    public function whereLike(string $search): PostQuery
    {
        $this->params['s'] = $search;
        return $this;
    }

    /**
     * Perform an exact search for a term in the post title, excerpt, and content.
     *
     * @param string $search String to search for.
     * @return $this
     */
    public function whereExact(string $search): PostQuery
    {
        $this->params['exact'] = true;
        return $this->whereLike($search);
    }

    /**
     * Return posts by slug.
     *
     * @param string|string[] $slug Slug(s) to search for.
     * @return $this
     */
    public function whereSlug($slug): PostQuery
    {
        if (\is_array($slug)) {
            $this->params['post_name__in'] = $slug;
        } else {
            $this->params['name'] = $slug;
        }
        return $this;
    }

    /**
     * Limit results to child terms of the supplied parent term_id/WP_Term.
     *
     * @param int|int[]|WP_Post|WP_Post[] $post_ids
     * @return $this
     */
    public function childOf($post_ids): PostQuery
    {
        if (\is_array($post_ids)) {
            $this->params['post_parent__in'] = $this->maybeConvertPostsToIds($post_ids);
        } else {
            $this->params['post_parent'] = $this->maybeConvertPostsToIds($post_ids);
        }
        return $this;
    }

    /**
     * Will remove any children of the supplied term_id(s).
     *
     * @param int|int[]|WP_Post|WP_Post[] $post_ids
     * @return $this
     */
    public function notChildOf($post_ids): PostQuery
    {
        $this->params['post_parent__not_in'] = $this->maybeConvertPostsToIds($post_ids);
        return $this;
    }

    /**
     * Offset the returned posts.
     *
     * @param int The offset amount.
     * @return $this
     */
    public function offset(int $amount): PostQuery
    {
        $this->params['offset'] = $amount;
        return $this;
    }

    /**
     * Set pagination.
     *
     * @param int Page number to fetch.
     * @return $this
     */
    public function page(int $page): PostQuery
    {
        $this->params['paged'] = $page;
        return $this;
    }

    /**
     * Limit the amount of returned posts.
     *
     * @param int The limit amount.
     * @return $this
     */
    public function limit(int $amount): PostQuery
    {
        $this->params['posts_per_page'] = $amount;
        return $this;
    }

    /**
     * Limit returned posts to the supplied IDs.
     *
     * @param int|int[] $ids Array of ID to search within.
     * @return $this
     */
    public function in($ids): PostQuery
    {
        if (!\is_array($ids)) {
            $ids = [$ids];
        }

        $this->params['post__in'] = $ids;
        $this->params['posts_per_page'] = \count($ids);
        return $this;
    }

    /**
     * Exclude posts from results by their IDs.
     *
     * @param int|int[] $exclude ID or IDs to exclude.
     * @return $this
     */
    public function exclude($exclude): PostQuery
    {
        if (\is_int($exclude)) {
            $exclude = [$exclude];
        }

        $this->params['post__not_in'] = $exclude;
        return $this;
    }

    /**
     * Returns the current tax_query.
     *
     * @return array
     */
    public function getTaxQuery(): array
    {
        return $this->tax_query;
    }

    /**
     * Returns the user ID from a WP_User instance.
     *
     * @param \WP_User $wp_user Instance.
     * @return int
     */
    private function getIdFromWpUser(WP_User $wp_user): int
    {
        return $wp_user->ID;
    }

    /**
     * Perform a query and return a Collection of WP_Posts.
     *
     * @param array $args WP_Query arguments.
     * @return \Tightenco\Collect\Support\Collection;
     */
    private function getCollection(array $args): Collection
    {
        $query = new WP_Query($args);
        return new Collection($query->posts);
    }

    /**
     * Perform a query and return a WP_Post object.
     *
     * @param array $args WP_Query arguments.
     * @return null|WP_Post
     */
    private function getPost(array $args): ?WP_Post
    {
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->post;
        }

        return null;
    }

    /**
     * Create the WP_Query arguments for the current query.
     *
     * @param array $args WP_Query arguments.
     * @return array
     */
    private function createArguments(array $args): array
    {
        if ($this->meta_query !== []) {
            $args['meta_query'] = $this->meta_query;
        }

        if ($this->tax_query !== []) {
            $args['tax_query'] = $this->tax_query;
        }

        if ($this->date_query !== []) {
            $args['date_query'] = $this->date_query;
        }

        $args = \array_merge_recursive($this->params, $args);

        if (isset($args['paged'], $args['offset'])) {
            $args['offset'] *= $args['paged'];
        }

        return $args;
    }

    /**
     * Generate tax query args.
     *
     * @param string|callable $key Taxonomy, or Callable for nested queries.
     * @param int|string|array $terms Taxonomy term(s).
     * @param string $operator Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS'
     *                                           and 'NOT EXISTS'.
     * @param bool $include_children Whether or not to include children for hierarchical taxonomies.
     *                                           Defaults to true.
     * @return array
     */
    private function generateTaxQueryArgs($key, $terms, $operator, $include_children): array
    {
        $args = [
            'taxonomy' => $key,
            'terms' => $terms,
        ];

        if (\is_int($terms) || (\is_array($terms) && isset($terms[0]) && \is_int($terms[0]))) {
            $args['field'] = 'term_id';
        } else {
            $args['field'] = 'slug';
        }

        if ($operator !== 'IN') {
            $args['operator'] = $operator;
        }

        if ($include_children === false) {
            $args['include_children'] = $include_children;
        }

        if ($operator === 'IN' && empty($terms)) {
            unset($args['terms'], $args['field']);
            $args['operator'] = 'EXISTS';
        }

        if ($operator === 'EXISTS' || $operator === 'NOT EXISTS') {
            unset($args['terms'], $args['field']);
        }

        return $args;
    }

    /**
     * Performs a find() for multiple ids or slugs.
     *
     * @param array $search Search terms.
     * @return \Tightenco\Collect\Support\Collection;
     */
    private function findMultiple(array $search): Collection
    {
        if (\is_numeric(\current($search))) {
            $this->in($search);
        } else {
            $this->whereSlug($search);
        }

        return $this->getCollection(
            $this->createArguments(
                [
                    'post_type' => $this->name,
                    'no_found_rows' => true,
                    'limit' => \count($search),
                ]
            )
        );
    }

    /**
     * Performs a find() for a single id or slug.
     *
     * @param string|int $search Search term.
     * @return null|WP_Post
     */
    private function findSingle($search): ?WP_Post
    {
        $args = [
            'post_type' => $this->name,
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ];

        if (\is_numeric($search)) {
            $args['p'] = $search;
        } else {
            $this->whereSlug($search);
        }

        return $this->getPost(
            $this->createArguments($args)
        );
    }

    /**
     * Convert a WP_Post or array of WP_Posts into IDs.
     *
     * @param WP_Post|WP_Post[]|int $input Input.
     * @return array|int
     */
    private function maybeConvertPostsToIds($input)
    {
        if (\is_array($input)) {
            foreach ($input as $key => $value) {
                if ($value instanceof WP_Post) {
                    $input[$key] = $value->ID;
                }
            }
        } elseif ($input instanceof WP_Post) {
            $input = $input->ID;
        }

        return $input;
    }
}
