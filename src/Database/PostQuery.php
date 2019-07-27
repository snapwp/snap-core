<?php

namespace Snap\Database;



use WP_Query;

// todo add find to accept search q or id
// todo add orderby
class PostQuery extends Query
{
    private $tax_query = [];

    /**
     * PostQuery constructor.
     *
     * @param string|array $type The post type to query.
     */
    public function __construct($type = 'post')
    {
        parent::__construct($type);
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
     * Return the first found WP_Term object.
     *
     * @param $id
     * @return false|\WP_Term|array
     */
    public function find($id)
    {
        if (\is_array($id)) {
            return $this->getArray(
                $this->createArguments([
                    'post__in' => $id,
                    'post_type' => $this->name,
                    'posts_per_page' => count($id),
                    'no_found_rows' => true,
                ])
            );
        }

        return $this->getPost(
            $this->createArguments([
                'p' => $id,
                'post_type' => $this->name,
                'posts_per_page' => 1,
                'no_found_rows' => true,
            ])
        );
    }

    /**
     * Add a tax query.
     *
     * @param  string|callable $key              Taxonomy, or Callable for nested queries.
     * @param int|string|array $terms            Taxonomy term(s).
     * @param string           $operator         Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS'
     *                                           and 'NOT EXISTS'.
     * @param bool             $include_children Whether or not to include children for hierarchical taxonomies.
     *                                           Defaults to true.
     * @return $this
     */
    public function taxonomy($key, $terms = '', string $operator = 'IN', bool $include_children = true)
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
     * @param  string|callable $key              Taxonomy, or Callable for nested queries.
     * @param int|string|array $terms            Taxonomy term(s).
     * @param string           $operator         Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS'
     *                                           and 'NOT EXISTS'.
     * @param bool             $include_children Whether or not to include children for hierarchical taxonomies.
     *                                           Defaults to true.
     * @return $this
     */
    public function orTaxonomy($key, $terms = '', string $operator = 'IN', bool $include_children = true)
    {
        $this->tax_query = ['relation' => 'OR'] + $this->tax_query;
        $this->taxonomy($key, $terms, $operator, $include_children);
        return $this;
    }




    /**
     * Limit the amount of returned posts.
     *
     * @param int The limit amount.
     * @return $this
     */
    public function limit(int $amount)
    {
        $this->params['posts_per_page'] = $amount;
        return $this;
    }

    /**
     * Offset the returned posts.
     *
     * @param int The offset amount.
     * @return $this
     */
    public function offset(int $amount)
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
    public function page(int $page)
    {
        $this->params['paged'] = $page;
        return $this;
    }

    /**
     * Exclude posts from results by their IDs.
     *
     * @param int|array $exclude ID or IDs to exclude.
     * @return $this
     */
    public function exclude($exclude)
    {
        if (\is_int($exclude)) {
            $exclude = [$exclude];
        }

        $this->params['post__not_in'] = $exclude;
        return $this;
    }

    /**
     * Limit returned posts to the supplied IDs.
     *
     * @param array|int $ids Array of ID to search within.
     * @return $this
     */
    public function in($ids)
    {
        if (!\is_array($ids)) {
            $ids = [$ids];
        }

        $this->params['post__in'] = $ids;
        $this->params['posts_per_page'] = \count($ids);
        return $this;
    }

    /**
     * Returns the first found WP_Post.
     *
     * @return bool|\WP_Post
     */
    public function first()
    {
        return $this->getPost(
            $this->createArguments([
                'post_type' => $this->name,
                'posts_per_page' => 1,
                'no_found_rows' => true,
            ])
        );
    }

    /**
     * Return found WP_Posts.
     *
     * @return array
     */
    public function get()
    {
        return $this->getArray(
            $this->createArguments([
                'post_type' => $this->name,
                'no_found_rows' => true,
            ])
        );
    }

    /**
     * Return a standard WP_Query object for looping through.
     *
     * @return \WP_Query
     */
    public function getWPQuery()
    {
        return new WP_Query(
            $this->createArguments([
                'post_type' => $this->name,
            ])
        );
    }

    /**
     * Return all found WP_Posts with no pagination, ignoring any additional arguments.
     *
     * @return array
     */
    public function all()
    {
        return $this->getArray([
            'post_type' => $this->name,
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ]);
    }



    public function getTaxQuery()
    {
        return $this->tax_query;
    }

    /**
     * Perform a query and return an array of WP_Posts.
     *
     * @param array $args WP_Query arguments.
     * @return array
     */
    private function getArray(array $args)
    {
        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Perform a query and return a WP_Post object.
     *
     * @param array $args WP_Query arguments.
     * @return bool|\WP_Post
     */
    private function getPost(array $args)
    {
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->post;
        }

        return false;
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

        $args = \array_merge_recursive($this->params, $args);

        if (isset($args['paged']) && isset($args['offset'])) {
            $args['offset'] = $args['offset'] * $args['paged'];
        }

        return $args;
    }

    /**
     * @param $key
     * @param $terms
     * @param $operator
     * @param $include_children
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
            $args['field'] = 'name';
        }

        if ($operator !== 'IN') {
            $args['operator'] = $operator;
        }

        if ($include_children === false) {
            $args['include_children'] = $include_children;
        }

        return $args;
    }
}