<?php

namespace Snap\Hookables\Content;

class ColumnManager
{
    private $output = [];

    /**
     * Columns to remove.
     *
     * @var array
     */
    private $to_remove = [];

    /**
     * Columns to add.
     *
     * @var array
     */
    private $to_add = [];
    private $to_sort = [];
    private $to_move = [];

    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Remove existing columns.
     *
     * @param string|array $column The column key to remove, or array of keys.
     * @return $this
     */
    public function remove($column)
    {
        if (\is_array($column)) {
            $this->to_remove = \array_unique(\array_merge($this->to_remove, $column));
        } else {
            if (!\in_array($column, $this->to_remove)) {
                $this->to_remove[] = $column;
            }
        }

        return $this;
    }

    /**
     * Add one or more admin columns.
     *
     * @param string|array $column The column to add. Or can be an array of names => labels to add many at once.
     * @param string|null  $label  The label to show to an admin. Defaults to the sanitized $column.
     * @return $this
     */
    public function add($column, string $label = null)
    {
        if (!\is_array($column)) {
            $this->populateWithMeta($column);
            $column = [$column => $label];
        }

        foreach ($column as $column_name => $label) {
            if (\is_int($column_name)) {
                $column_name = $label;
                $label = null;
            }

            if ($label === null) {
                $label = \str_replace(['_', '-'], ' ', \ucwords($column_name));
            }

            $this->to_add[$column_name] = $label;
            $this->populateWithMeta($column_name);
        }


        return $this;
    }

    /**
     * Populate a column, by providing a callable which prints content, or a meta key print the value for.
     *
     * @param string               $column   Column key.
     * @param callable|string $callback Callback function or meta key to use for output.
     * @return $this
     */
    public function populate(string $column, $callback)
    {
        if (\is_callable($callback)) {
            $this->output[$column] = $callback;
            return $this;
        }

        return $this->populateWithMeta($column, $callback);
    }

    public function sort($column, $sort_by = null)
    {
        if ($sort_by === null) {
            $sort_by = $column;
        }
        $this->to_sort[$column] = $sort_by;
        return $this;
    }

    /**
     * Reorder a single column.
     *
     * @param string $column The column key.
     * @param int    $index  The position to move it to.
     * @return $this
     */
    public function move(string $column, int $index)
    {
        $this->to_move[$column] = $index;
        return $this;
    }

    /**
     * Move multiple columns at once.
     *
     * @param array $positions Array of column keys => new positions.
     * @return $this
     */
    public function reorder(array $positions)
    {
        $this->to_move = \array_merge($this->to_move, $positions);
        return $this;
    }

    /**
     * Get all all custom columns.
     *
     * @return array
     */
    public function getCustomColumns(): array
    {
        return $this->to_add;
    }

    /**
     * Get all all columns which should be removed.
     *
     * @return array
     */
    public function getColumnsToRemove(): array
    {
        return $this->to_remove;
    }

    /**
     * Get all all columns which should be moved.
     *
     * @return array
     */
    public function getColumnPositions(): array
    {
        return $this->to_move;
    }

    /**
     * Get all all custom column output callbacks.
     *
     * @param string $key Column to get the callback for.
     * @return callable|null
     */
    public function getColumnCallback(string $key)
    {
        return isset($this->output[$key]) ? $this->output[$key] : null;
    }

    /**
     * Get sortable columns.
     *
     * @param null $key Optional specific key to fetch.
     * @return array|null
     */
    public function getSortableColumns($key = null)
    {
        if ($key !== null) {
            return isset($this->to_sort[$key]) ? $this->to_sort[$key] : null;
        }

        return $this->to_sort;
    }

    /**
     * Shorthand to populate a column with the value of a given $meta_key.
     *
     * @param string $column   The column to populate.
     * @param string $meta_key The meta key to output.
     * @return $this
     */
    private function populateWithMeta(string $column, string $meta_key = null)
    {
        if ($meta_key === null) {
            $meta_key = $column;
        }

        $callback = function ($post_id) use ($meta_key) {
            echo \esc_html((string)\get_post_meta($post_id, $meta_key, true));
        };

        if ($this->type === 'taxonomy') {
            $callback = function ($post_id) use ($meta_key) {
                echo \esc_html((string)\get_term_meta($post_id, $meta_key, true));
            };
        }

        $this->output[$column] = $callback;

        return $this;
    }
}
