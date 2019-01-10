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

        if (!empty($this->aliases())) {
            $this->set_aliases($this->aliases());
        }
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
     * Set the validation error messages.
     *
     * @since  1.0.0
     *
     * @param array $messages Error messages as key value pairs.
     * @return $this
     */
    public function set_error_messages(array $messages = [])
    {
        $this->validation->set_error_messages($messages);

        return $this;
    }

    /**
     * Set aliases for use in your error messages.
     *
     * In error messages :attribute can be used to substitute with the input array key into the message.
     * The key might not be ideal, so you can provide a better substitute as an alias.
     *
     * @since  1.0.0
     *
     * @param array $aliases Key value pairs as original => alias.
     */
    public function set_aliases(array $aliases = [])
    {
        $this->validation->set_aliases($aliases);
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
     * Get errors from the internal validation instance as a multi-dimensional array.
     *
     * @since  1.0.0
     *
     * @return array Errors.
     */
    public function get_errors($key = null, string $format = ':message'): array
    {
        return $this->validation->get_errors($key, $format);
    }

    /**
     * Get count of all errors.
     *
     * @since  1.0.0
     *
     * @return int
     */
    public function get_error_count(): int
    {
        return $this->validation->get_error_count();
    }

    /**
     * Returns a flat array of all errors.
     *
     * @since 1.0.0
     *
     * @param string $format Optional. Format to wrap errors in such as '<li>:message</li>'.
     *                       Defaults to ':message'.
     * @return array
     */
    public function get_all_errors(string $format = ':message')
    {
        return $this->validation->get_all_errors($format);
    }

    /**
     * Checks if an error exists.
     *
     * @since 1.0.0
     *
     * @param string $key The key to search for. EG. 'name' or 'uploads.*'.
     * @return bool
     */
    public function has_error(string $key): bool
    {
        return $this->validation->has_error($key);
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

    /**
     * Can be overloaded to provide default aliases to the Validation instance.
     *
     * @since 1.0.0
     *
     * @return array Array of messages.
     */
    public function aliases()
    {
        return [];
    }
}
