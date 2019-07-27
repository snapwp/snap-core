<?php

namespace Snap\Database;

use Snap\Utils\Collection;
use WP_Term_Query;

class TaxQuery extends Query
{
    /**
     * PostQuery constructor.
     *
     * @param string|array $name The taxonomy to query.
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * Set the taxonomy name(s) to query.
     *
     * @param string|array $name The taxonomy name(s) to query.
     * @return $this
     */
    public function tax($name)
    {
        if ($this->name === null) {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * Returns a count of all published terms for the current taxonomies(s) taking current params into account.
     *
     * @return int
     */
    public function count(): int
    {
        if (\is_array($this->name)) {
            $total = 0;

            foreach ($this->name as $type) {
                $total += (int)\wp_count_terms($type, $this->createArguments());
            }

            return $total;
        }

        return (int)\wp_count_terms($this->name, $this->createArguments());
    }

    /**
     * Return the first found WP_Term object.
     *
     * @param $id
     * @return false|\WP_Term|\Snap\Utils\Collection
     */
    public function find($id)
    {
        $this->includeEmpty();

        if (\is_array($id)) {
            return $this->getCollection(
                $this->createArguments(
                    [
                        'taxonomy' => $this->name,
                        'limit' => \count($id),
                    ]
                )
            );
        }

        return $this->getTerm(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                    'limit' => 1,
                ]
            )
        );
    }

    /**
     * Return found WP_Terms.
     *
     * @return \Snap\Utils\Collection
     */
    public function get(): Collection
    {
        return $this->getCollection(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                ]
            )
        );
    }

    /**
     * Return found term_ids.
     *
     * @return array
     */
    public function getIds(): array
    {
        return $this->getArray(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                    'fields' => 'ids',
                ]
            )
        );
    }

    /**
     * Return an associative array with term_ids as keys, and term names as values.
     *
     * @return array
     */
    public function getNames(): array
    {
        return $this->getArray(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                    'fields' => 'id=>name',
                ]
            )
        );
    }

    /**
     * Return an associative array with term_ids as keys, and term slugs as values.
     *
     * @return array
     */
    public function getSlugs(): array
    {
        return $this->getArray(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                    'fields' => 'id=>slug',
                ]
            )
        );
    }

    /**
     * Return the first found WP_Term object.
     *
     * @return false|\WP_Term
     */
    public function first()
    {
        return $this->getTerm(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                    'limit' => 1,
                ]
            )
        );
    }

    /**
     * Return the raw WP_Term_Query object.
     *
     * @return \WP_Term_Query
     */
    public function getQueryObject()
    {
        return new WP_Term_Query(
            $this->createArguments(
                [
                    'taxonomy' => $this->name,
                ]
            )
        );
    }

    /**
     * Fetches terms that belong to the provides post IDs/objects.
     *
     * @param int|\WP_Post|int[]|\WP_Post[] $object_ids A single post ID/WP_Post or an array of IDs/WP_Post objects.
     * @return $this
     */
    public function for($object_ids)
    {
        if ($object_ids instanceof \WP_Post) {
            $object_ids = $object_ids->ID;
        }

        if (\is_array($object_ids)) {
            foreach ($object_ids as $key => $object) {
                if ($object instanceof \WP_Post) {
                    $object_ids[$key] = $object->ID;
                }
            }
        }

        $this->params['object_ids'] = $object_ids;
        return $this;
    }

    /**
     * Include empty terms.
     *
     * @return $this
     */
    public function includeEmpty()
    {
        $this->params['hide_empty'] = false;
        return $this;
    }

    /**
     * Include only terms that have posts.
     *
     * @return $this
     */
    public function hideEmpty()
    {
        $this->params['hide_empty'] = true;
        return $this;
    }

    /**
     * Limit results to child terms of the supplied parent term_id/WP_Term.
     *
     * @param int|\WP_Term $parent The parent term_id/WP_Term object.
     * @return $this
     */
    public function childOf($parent)
    {
        $this->params['child_of'] = $this->maybeConvertTermsToIds($parent);
        return $this;
    }

    /**
     * Will remove any children of the supplied term_id(s).
     *
     * @param int|int[]|\WP_Term|\WP_Term[] $term_ids
     * @return $this
     */
    public function notChildOf($term_ids)
    {
        $this->params['exclude_tree'] = $this->maybeConvertTermsToIds($term_ids);
        return $this;
    }

    /**
     * Limit results to only direct children of the supplied parent ID/WP_Term.
     *
     * @param int|\WP_Term $parent The parent ID/WP_Term object.
     * @return $this
     */
    public function directChildOf($parent)
    {
        $this->params['parent'] = $this->maybeConvertTermsToIds($parent);
        return $this;
    }

    /**
     * Only include terms that do not have children.
     *
     * @return $this
     */
    public function childless()
    {
        $this->params['childless'] = true;
        return $this;
    }

    /**
     * Limit results to the term_ids specified.
     *
     * @param int|int[] $term_ids The term_ids to limit the query to.
     * @return $this
     */
    public function in($term_ids)
    {
        $this->params['include'] = $term_ids;
        return $this;
    }

    /**
     * Omit the term_ids specified from results.
     *
     * @param int|int[] $term_ids The term_ids to omit.
     * @return $this
     */
    public function exclude($term_ids)
    {
        $this->params['exclude'] = $term_ids;
        return $this;
    }

    /**
     * Query for direct matches against term name.
     *
     * @param string|string[] $names Term name(s) to return.
     * @return $this
     */
    public function whereName($names)
    {
        $this->params['name'] = $names;
        return $this;
    }

    /**
     * Query for LIKE matches against term name.
     *
     * @param string $name Term name to search for.
     * @return $this
     */
    public function whereNameLike(string $name)
    {
        $this->params['name__like'] = $name;
        return $this;
    }
    /**
     * Query for direct matches against term slug.
     *
     * @param string|string[] $slugs Slug(s) to get matches for.
     * @return $this
     */
    public function whereSlug($slugs)
    {
        $this->params['slug'] = $slugs;
        return $this;
    }

    /**
     * Query for direct matches against term_taxonomy_ids.
     *
     * @param int|int[] $ids Term taxonomy_ids to get matches for.
     * @return $this
     */
    public function whereTaxonomyId($ids)
    {
        $this->params['term_taxonomy_id'] = $ids;
        return $this;
    }

    /**
     * Query for LIKE matches against term description.
     *
     * @param string $description Term description to search for.
     * @return $this
     */
    public function whereDescriptionLike(string $description)
    {
        $this->params['description__like'] = $description;
        return $this;
    }

    /**
     * Query for LIKE matches against term name and slug.
     *
     * @param string $name The search term.
     * @return $this
     */
    public function whereLike(string $name)
    {
        $this->params['search'] = $name;
        return $this;
    }

    /**
     * Include post counts.
     *
     * @param string $order_by What field to order by.
     * @param string $order    Direction of ordering: ASC or DESC.
     * @return $this
     */
    public function orderBy(string $order_by, string $order = 'ASC')
    {
        $this->params['orderby'] = $order_by;
        $this->params['order'] = \strtoupper($order);
        return $this;
    }

    /**
     * Limit the amount of returned terms.
     *
     * @param int The limit amount.
     * @return $this
     */
    public function limit(int $amount)
    {
        $this->params['number'] = $amount;
        return $this;
    }

    /**
     * Offset the returned terms.
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
     * Perform a query and return an array of WP_Terms.
     *
     * @param array $args WP_Query arguments.
     * @return array
     */
    private function getArray(array $args): array
    {
        $query = new WP_Term_Query($args);
        return $query->terms;
    }

    /**
     * Perform a query and return a Collection of WP_Terms.
     *
     * @param array $args WP_Query arguments.
     * @return \Snap\Utils\Collection
     */
    private function getCollection(array $args): Collection
    {
        $query = new WP_Term_Query($args);
        return new Collection($query->terms);
    }

    /**
     * Perform a query and return a WP_Term object.
     *
     * @param array $args WP_Term_Query arguments.
     * @return false|\WP_Term
     */
    private function getTerm(array $args)
    {
        $query = new WP_Term_Query($args);

        if (!$query->terms) {
            return false;
        }

        return \current($query->terms);
    }

    /**
     * Create the WP_Term_Query arguments for the current query.
     *
     * @param array $args WP_Term_Query arguments.
     * @return array
     */
    private function createArguments(array $args = []): array
    {
        if (!empty($this->meta_query)) {
            $args['meta_query'] = $this->meta_query;
        }

        $args = \array_merge_recursive($this->params, $args);

        return $args;
    }

    /**
     * Convert a WP_Term or array of terms into term_ids.
     *
     * @param \WP_Term|\WP_Term[] $input Input.
     * @return array|int
     */
    private function maybeConvertTermsToIds($input)
    {
        if (\is_array($input)) {
            foreach ($input as $key => $value) {
                if ($value instanceof \WP_Term) {
                    $input[$key] = $value->term_id;
                }
            }
        } elseif ($input instanceof \WP_Term) {
            $input = $input->term_id;
        }

        return $input;
    }
}
