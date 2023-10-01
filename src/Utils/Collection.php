<?php

namespace Snap\Utils;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Countable;
use WP_List_Util;

/**
 * Simple collection class for working with arrays.
 *
 * @since 1.0.0
 */
class Collection implements Countable, ArrayAccess, IteratorAggregate
{
    /**
     * The items contained in the collection.
     *
     * @since 1.0.0
     * @var array
     */
    protected $items = [];

    /**
     * Create the collection and add items.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @return array
     */
    public function all()
    {
        return (array) $this->items;
    }

    /**
     * Creates a new collection with specific values from the current collection items.
     *
     * @since 1.0.0
     *
     * @param int|string $field     Field from the object to place instead of the entire object.
     * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
     *                              Default null.
     * @return Collection If `$index_key` is set, an array of found values with keys
     *                    corresponding to `$index_key`. If $index_key is null, array keys will be preserved.
     */
    public function pluck($field, $index_key = null)
    {
        $list = new WP_List_Util($this->all());

        return new static($list->pluck($field, $index_key));
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @since 1.0.0
     *
     * @param  int   $depth How many level to flatten.
     * @param  array $array Private.
     * @return Collection
     */
    public function flatten($depth = INF, $array = null)
    {
        $result = [];

        if ($array === null) {
            $array = $this->all();
        }

        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! \is_array($item)) {
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
     * @since 1.0.0
     *
     * @return Collection
     */
    public function reverse()
    {
        return new static(\array_reverse($this->all()));
    }

    /**
     * Run a filter over each of the items.
     *
     * @since 1.0.0
     *
     * @param  callable|null $callback Optional callback to use when filtering options.
     *                                 Must return bool to indicate if item passed check.
     * @return Collection
     */
    public function filter(callable $callback = null)
    {
        if ($callback === null) {
            return new static(\array_filter($this->all()));
        }

        return new static(\array_filter($this->all(), $callback));
    }

    /**
     * Sanity check items being passed into constructor.
     *
     * @since 1.0.0
     *
     * @param  mixed $items The items being added to the collection.
     * @return array
     */
    protected function getItems($items)
    {
        if (\is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof Traversable) {
            return \iterator_to_array($items);
        }
        return (array) $items;
    }

    /**
     * Count elements within this collection.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @param mixed $offset Key to search for.
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->items);
    }

    /**
     * Offset to retrieve
     *
     * @since 1.0.0
     *
     * @param mixed $offset Key to get.
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[ $offset ];
    }

    /**
     * Offset to set
     *
     * @since 1.0.0
     *
     * @param mixed $offset Key to set.
     * @param mixed $value The value to set.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (\is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[ $offset ] = $value;
        }
    }

    /**
     * Offset to unset.
     *
     * @since 1.0.0
     *
     * @param mixed $offset The offset to retrieve.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[ $offset ]);
    }

    /**
     * Retrieve an external iterator.
     *
     * @since 1.0.0
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
