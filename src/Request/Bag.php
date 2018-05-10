<?php

namespace Snap\Core\Request;

class Bag
{
    private $data = [];

    public function __construct($contents)
    {
        $this->data = $contents;
    }

    public function get($key, $default = null)
    {

        if (isset($this->data[ $key ])) {
            return $this->data[ $key ];
        } else {
            return $default;
        }
    }

    // has
    //
    // trimm all params
    public function get_numeric($key, $default = null)
    {
        return filter_var($this->get($key, $default), FILTER_SANITIZE_NUMBER_FLOAT);
    }

    // getInt
    // getFloat
    // get_regex
    public function to_array()
    {
        return $this->data;
    }
}
