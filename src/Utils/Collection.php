<?php

namespace Snap\Utils;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use WP_List_Util;

/**
 * Simple collection class for working with arrays.
 */
class Collection implements Countable, ArrayAccess, IteratorAggregate
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create the collection and add items.
     *
     * @param array $items Items to add.
     */
    public function __construct($items = [])
    {
        $this->items = $this->getItems($items);
    }

    /**
     * Get the collection contents as an array.
     *
     * @return array
     */
    public function all(): array
    {
        return (array)$this->items;
    }

    /**
     * Creates a new collection with specific values from the current collection items.
     *
     * @param int|string $field     Field from the object to place instead of the entire object.
     * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
     *                              Default null.
     * @return Collection If `$index_key` is set, an array of found values with keys
     *                              corresponding to `$index_key`. If $index_key is null, array keys will be preserved.
     */
    public function pluck($field, $index_key = null): Collection
    {
        $list = new WP_List_Util($this->all());

        return new static($list->pluck($field, $index_key));
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  int $depth   How many level to flatten.
     * @param  array $array Private.
     * @return Collection
     */
    public function flatten($depth = INF, $array = null): Collection
    {
        $result = [];

        if ($array === null) {
            $array = $this->all();
        }

        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (!\is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = \array_merge($result, \array_values($item));
            } else {
                $result = \array_merge($result, $this->flatten($depth - 1, $item)->all());
            }
        }

        return new static($result);
    }

    /**
     * Reverse the items.
     *
     * @return Collection
     */
    public function reverse(): Collection
    {
        return new static(\array_reverse($this->all()));
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null $callback Optional callback to use when filtering options.
     *                                 Must return bool to indicate if item passed check.
     * @return Collection
     */
    public function filter(callable $callback = null): Collection
    {
        if ($callback === null) {
            return new static(\array_filter($this->all()));
        }

        return new static(\array_filter($this->all(), $callback));
    }

    /**
     * Sanity check items being passed into constructor.
     *
     * @param  mixed $items The items being added to the collection.
     * @return array
     */
    protected function getItems($items): array
    {
        if (\is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof Traversable) {
            return \iterator_to_array($items);
        }
        return (array)$items;
    }

    /**
     * Count elements within this collection.
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return \count($this->all());
    }

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset Key to search for.
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->items);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset Key to get.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * Offset to set
     *
     * @param mixed $offset Key to set.
     * @param mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Offset to unset.
     *
     * @param mixed $offset The offset to retrieve.
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Retrieve an external iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
