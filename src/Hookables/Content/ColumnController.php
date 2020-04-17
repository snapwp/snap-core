<?php

namespace Snap\Hookables\Content;

use Snap\Hookables\ContentHookable;
use WP_Query;
use WP_Term_Query;

/**
 * Deals with the display and functionality of admin columns.
 */
class ColumnController
{
    /**
     * The ContentHookable object.
     *
     * @var \Snap\Hookables\ContentHookable
     */
    private $target;

    /**
     * Instance of ColumnManager.
     *
     * @var \Snap\Hookables\Content\ColumnManager
     */
    private $columnManager;

    /**
     * ColumnController constructor.
     *
     * @param \Snap\Hookables\ContentHookable       $target        The ContentHookable object.
     * @param \Snap\Hookables\Content\ColumnManager $columnManager Instance of ColumnManager.
     */
    public function __construct(ContentHookable $target, ColumnManager $columnManager)
    {
        $this->target = $target;
        $this->columnManager = $columnManager;
    }

    /**
     * Handle adding and removing columns from the admin screen.
     *
     * @param array $columns Existing columns array.
     * @return array
     */
    public function manageColumns(array $columns): array
    {
        $columns = $columns + $this->columnManager->getCustomColumns();

        foreach ($this->columnManager->getColumnsToRemove() as $column) {
            unset($columns[$column]);
        }

        foreach ($this->columnManager->getColumnPositions() as $key => $position) {
            // find index of the element in the array, and save it to a variable.
            $index = \array_search($key, \array_keys($columns));
            $item = \array_slice($columns, $index, 1);
            unset($columns[$key]);

            // split columns array into two at the desired position
            $start = \array_slice($columns, 0, $position, true);
            $end = \array_slice($columns, $position, \count($columns) - 1, true);

            // insert column into position
            $columns = $start + $item + $end;
        }

        return $columns;
    }

    /**
     * Handle outputting column content.
     *
     * Calls the registered callback for the column, and passed the object id as a parameter.
     *
     * @param array $args The arguments passed from the hooked filter.
     */
    public function handleColumnOutput(...$args)
    {
        $args = \array_values(\array_filter($args));

        if ($this->columnManager->getColumnCallback($args[0]) !== null) {
            \call_user_func($this->columnManager->getColumnCallback($args[0]), $args[1]);
            return;
        }
    }

    /**
     * Add any sortable columns into the sortable columns array.
     *
     * @param array $columns Current sortable columns.
     * @return array
     */
    public function setSortableColumns(array $columns): array
    {
        $flat_array = \array_keys($this->columnManager->getSortableColumns());
        return $columns + \array_combine($flat_array, $flat_array);
    }

    /**
     * Adds sort handlers for use on the admin table view.
     *
     * @param \WP_Term_Query|\WP_Query $query The query instance.
     */
    public function handleSortableColumns($query)
    {
        if ($query instanceof WP_Term_Query) {
            $this->handleTermSortableColumns($query);
            return;
        }

        $this->handlePostSortableColumns($query);
    }

    /**
     * Handle post sortable columns.
     *
     * @param \WP_Query $query Current WP_Query.
     */
    private function handlePostSortableColumns(WP_Query $query)
    {
        // Bail if we are not on the correct admin page.
        if (!$query->is_main_query() || !\is_admin() || $query->get('post_type') !== $this->target->getName()) {
            return;
        }

        $order_by = $query->get('orderby');
        $column = $this->columnManager->getSortableColumns($order_by);

        if ($column !== null) {
            if (\is_callable($column)) {
                $column($query);
                return;
            }

            if ($column !== null) {
                $query->set('meta_key', $column);
                $query->set('orderby', 'meta_value');
            }
        }
    }

    /**
     * Handle Taxonomy sortable columns.
     *
     * @param \WP_Term_Query $query Current WP_Term_Query.
     */
    private function handleTermSortableColumns(WP_Term_Query $query)
    {
        // Bail if we are not on the correct admin page.
        if (!\is_admin() || !\in_array($this->target->getName(), $query->query_vars['taxonomy'])) {
            return;
        }

        $order_by = $query->query_vars['orderby'];
        $column = $this->columnManager->getSortableColumns($order_by);

        if ($column !== null) {
            if (\is_string($column)) {
                $query->query_vars['meta_key'] = $column;
                $query->query_vars['orderby'] = 'meta_value';
                return;
            }
            if (\is_callable($column)) {
                $column($query);
            }
        }
    }
}
