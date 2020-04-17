<?php

namespace Snap\Http\Request;

use ArrayAccess;
use Rakit\Validation\Helper;

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
        $this->setData($contents);
    }

    /**
     * Checks if a key is present in the bag.
     *
     * @param  string $key Item key to check.
     * @return boolean
     */
    public function has(string $key): bool
    {
        return Helper::arrayGet($this->data, $key) !== null;
    }

    /**
     * Gets a sanitized value from the bag, or a supplied default if not present.
     *
     * Use get_raw to get an un-sanitized version (should you need to).
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            if ($this->getRaw($key) === null) {
                return null;
            }

            if (\is_array($this->getRaw($key))) {
                return \array_map([$this, 'sanitiseArray'], $this->getRaw($key));
            }

            if ($this->getRaw($key) instanceof File) {
                return $this->getRaw($key);
            }

            return \trim(\sanitize_textarea_field($this->getRaw($key)));
        }

        return $default;
    }

    /**
     * Returns a value from the bag without any sanitation.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function getRaw(string $key, $default = null)
    {
        return Helper::arrayGet($this->data, $key, $default);
    }

    /**
     * Returns a value from the bag without any return characters, extra whitespace, or tabs.
     *
     * @param  string $key     Item key to fetch.
     * @param  string $default Default value if the key is not present.
     * @return string
     */
    public function getText(string $key, $default = null): string
    {
        return \sanitize_text_field(Helper::arrayGet($this->data, $key, $default));
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
    public function getNumeric(string $key, $default = null): string
    {
        return \filter_var(
            $this->get($key, $default),
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    /**
     * Return a value from the bag and cast as an int.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return int
     */
    public function getInt($key, $default = null): int
    {
        return (int)$this->getNumeric($key, $default);
    }

    /**
     * Return a value from the bag and cast as a float.
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return float
     */
    public function getFloat($key, $default = null): float
    {
        return (float)$this->getNumeric($key, $default);
    }

    /**
     * Only return a value if it matches the supplied regex pattern.
     *
     * Otherwise the $default is returned.
     *
     * @param  string $key     Item key to fetch.
     * @param  string $pattern A regex pattern to check against.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function getRegex($key, $pattern, $default = null)
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
     * Returns the sanitized values as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return \array_map([$this, 'sanitiseArray'], $this->data);
    }

    /**
     * Returns a JSON representation of all the sanitized values in this bag.
     *
     * @return string
     */
    public function toJson(): string
    {
        return \json_encode($this->toArray());
    }

    /**
     * Whether there is any content within this bag.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Callback for performing recursive sanitization on an array of values.
     *
     * @param  array|string $value The array to sanitise.
     * @return mixed
     */
    public function sanitiseArray($value)
    {
        if (\is_array($value)) {
            return $value;
        } else {
            if ($value instanceof File) {
                return $value;
            }

            return \trim(\sanitize_textarea_field($value));
        }
    }

    /**
     * Set an item.
     *
     * @param  mixed $offset The offset to set.
     * @param  mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Whether an item exists.
     *
     * @param  mixed $offset An offset to check for.
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Remove an item.
     *
     * @param  mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Get an item.
     *
     * @param  mixed $offset The offset to get.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset, null);
    }

    /**
     * Add the contents into the bag.
     *
     * @param array $contents The array of params to set.
     */
    protected function setData(array $contents = [])
    {
        $this->data = $contents;
    }
}
