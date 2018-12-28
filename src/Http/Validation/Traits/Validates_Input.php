<?php

namespace Snap\Http\Validation\Traits;

use Snap\Http\Validation\Validation;

/**
 * A simple trait which exposes Snap\Http\Validation\Validation to Requests.
 *
 * @package Snap\Http\Validation\Traits
 */
trait Validates_Input
{
    /**
     * @since 1.0.0
     * @var \Snap\Http\Validation\Validation
     */
    protected $validation;

    /**
     * Sets up the Snap\Http\Validation\Validation instance.
     *
     * @since 1.0.0
     *
     * @param array $data The data to validate.
     */
    protected function setup_validation($data)
    {
        $this->validation = new Validation($data, $this->rules(), $this->messages());
    }

    /**
     * Sets rules on the Validation instance.
     *
     * @since 1.0.0
     *
     * @param array $rule_set Array of rules to set.
     * @return $this
     */
    public function set_rules(array $rule_set = [])
    {
        $this->validation->set_rules($rule_set);

        return $this;
    }

    /**
     * Validates the request using the rules and messages set on the internal Validation instance.
     *
     * @since  1.0.0
     *
     * @return boolean If the data passed or not.
     */
    public function is_valid()
    {
        return $this->validation->is_valid();
    }

    /**
     * Get errors from the internal validation instance as an array.
     *
     * @since  1.0.0
     *
     * @return array Errors.
     */
    public function get_errors()
    {
        return $this->validation->get_errors();
    }

    /**
     * Can be overloaded to provide default rules to the Validation instance.
     *
     * @since 1.0.0
     *
     * @return array Array of rules.
     */
    public function rules()
    {
        return [];
    }

    /**
     * Can be overloaded to provide default error messages to the Validation instance.
     *
     * @since 1.0.0
     *
     * @return array Array of messages.
     */
    public function messages()
    {
        return [];
    }
}
