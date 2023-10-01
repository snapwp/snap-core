<?php

namespace Snap\Http\Request;

use ArrayAccess;
use Rakit\Validation\Helper;
use Snap\Http\Request\File\File;

/**
 * Parameter bag.
 */
class Bag implements ArrayAccess
{
    /**
     * Bag contents.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Creates the bag.
     *
     * @param array $contents Array of items (key => value pairs) to add to the bag.
     */
    public function __construct(array $contents = [])
    {
        $this->set_data($contents);
    }

    /**
     * Gets a sanitized value from the bag, or a supplied default if not present.
     *
     * Use get_raw to get an un-sanitized version (should you need to).
     *
     * @param  string $key     Item key to fetch.
     * @param mixed|null $default Default value if the key is not present.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            if ($this->get_raw($key) === null) {
                return null;
            }

            if (\is_array($this->get_raw($key))) {
                return \array_map([$this, 'sanitise_array'], $this->get_raw($key));
            }

            if ($this->get_raw($key) instanceof File) {
                return $this->get_raw($key);
            }

            return \sanitize_textarea_field($this->get_raw($key));
        }

        return $default;
    }

    /**
     * Returns a value from the bag without any sanitation.
     *
     * @param  string $key     Item key to fetch.
     * @param mixed|null $default Default value if the key is not present.
     * @return mixed
     */
    public function get_raw(string $key, mixed $default = null): mixed
    {
        return Helper::arrayGet($this->data, $key, $default);
    }

    /**
     * Checks if a key is present in the bag.
     */
    public function has(string $key): bool
    {
        return !empty(Helper::arrayGet($this->data, $key));
    }

    /**
     * Gets a numeric value from the bag.
     *
     * Only digits, decimals and the minus (-) characters are returned.
     * All other characters are stripped out.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return string
     */
    public function get_numeric(string $key, $default = null)
    {
        return \filter_var(
            $this->get($key, $default),
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    /**
     * Return a value from the bag cast as an int.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return int
     */
    public function get_int($key, $default = null): int
    {
        return (int)$this->get($key, $default);
    }

    /**
     * Return a value from the bag cast as a float.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return float
     */
    public function get_float($key, $default = null): float
    {
        return (float)$this->get($key, $default);
    }

    /**
     * Only return a value if it matches the supplied regex pattern.
     *
     * Otherwise the $default is returned.
     *
     * @param string $key     Item key to fetch.
     * @param string $pattern A regex pattern to check against.
     * @param mixed|null $default Default value if the key is not present.
     * @return mixed
     */
    public function get_regex(string $key, string $pattern, mixed $default = null): mixed
    {
        $filtered = \filter_var(
            $this->get($key),
            FILTER_VALIDATE_REGEXP,
            [
                "options" => [
                    "regexp" => $pattern,
                ],
            ]
        );

        if ($filtered === false) {
            return $default;
        }

        return $filtered;
    }

    /**
     * Returns the raw values as an array.
     *
     * @return array
     */
    public function to_array(): array
    {
        return $this->data;
    }

    /**
     * Returns a JSON representation of all the raw values in this bag.
     * @throws \JsonException
     */
    public function to_json(): string
    {
        return \json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /**
     * Whether there is any content within this bag.
     *
     * @return bool
     */
    public function is_empty(): bool
    {
        return empty($this->data);
    }

    /**
     * Callback for performing recursive sanitization on an array of values.
     */
    public function sanitise_array($value): string|array|File
    {
        if (\is_array($value)) {
            return $value;
        }

        if ($value instanceof File) {
            return $value;
        }

        return \sanitize_textarea_field($value);
    }

    /**
     * Set an item.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (\is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[ $offset ] = $value;
        }
    }

    /**
     * Whether an item exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Remove an item.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[ $offset ]);
    }

    /**
     * Get an item.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset, null);
    }

    /**
     * Add the contents into the bag.
     */
    protected function set_data(array $contents = []): void
    {
        $this->data = $contents;
    }
}
