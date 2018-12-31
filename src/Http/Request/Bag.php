<?php

namespace Snap\Http\Request;

use ArrayAccess;
use Snap\Http\Request\File\File;

/**
 * Parameter bag.
 */
class Bag implements ArrayAccess
{
    /**
     * Bag contents.
     *
     * @since  1.0.0
     * @var array
     */
    protected $data = [];

    /**
     * Creates the bag.
     *
     * @since 1.0.0
     *
     * @param array $contents Array of items (key => value pairs) to add to the bag.
     */
    public function __construct($contents = [])
    {
        $this->set_data($contents);
    }

    /**
     * Gets a sanitized value from the bag, or a supplied default if not present.
     *
     * Use get_raw to get an un-sanitized version (should you need to).
     *
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function get($key, $default = null)
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

            return \sanitize_text_field($this->get_raw($key));
        }

        return $default;
    }

    /**
     * Returns a value from the bag without any sanitation.
     *
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function get_raw($key, $default = null)
    {
        if (isset($this->data[ $key ]) && '' !== $this->data[ $key ]) {
            return $this->data[ $key ];
        }

        return $default;
    }

    /**
     * Checks if a key is present in the bag.
     *
     * @since 1.0.0
     *
     * @param  string $key Item key to check.
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->data[ $key ]);
    }

    /**
     * Gets a numeric value from the bag.
     *
     * Only digits, decimals and the minus (-) characters are returned.
     * All other characters are stripped out.
     *
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return string
     */
    public function get_numeric($key, $default = null)
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
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return int
     */
    public function get_int($key, $default = null)
    {
        return (int)$this->get($key, $default);
    }

    /**
     * Return a value from the bag cast as a float.
     *
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return float
     */
    public function get_float($key, $default = null)
    {
        return (float)$this->get($key, $default);
    }

    /**
     * Only return a value if it matches the supplied regex pattern.
     *
     * Otherwise the $default is returned.
     *
     * @since 1.0.0
     *
     * @param  string $key     Item key to fetch.
     * @param  string $pattern A regex pattern to check against.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed
     */
    public function get_regex($key, $pattern, $default = null)
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
     * @since 1.0.0
     *
     * @return array
     */
    public function to_array()
    {
        return $this->data;
    }

    /**
     * Returns a JSON representation of all the raw values in this bag.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_json()
    {
        return \json_encode($this->data);
    }

    /**
     * Callback for performing recursive sanitisation on an array of values.
     *
     * @since  1.0.0
     *
     * @param  array|string $value The array to sanitise.
     * @return mixed
     */
    public function sanitise_array($value)
    {
        if (\is_array($value)) {
            return $value;
        } else {
            if ($value instanceof File) {
                return $value;
            }

            return \sanitize_text_field($value);
        }
    }

    /**
     * Set an item.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset The offset to set.
     * @param  mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[ $offset ] = $value;
        }
    }

    /**
     * Whether an item exists.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset An offset to check for.
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Remove an item.
     *
     * @since  1.0.0
     *
     * @param  mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->data[ $offset ]);
    }

    /**
     * Get an item.
     *
     * @since  1.0.0
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
     * @since 1.0.0
     *
     * @param array $contents
     */
    protected function set_data($contents = [])
    {
        $this->data = $contents;
    }
}
