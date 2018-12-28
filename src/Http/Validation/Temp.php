<?php

class Temp
{
	    /**
     * Set the validation error messages.
     *
     * @since  1.0.0
     * @see    https://github.com/rakit/validation#custom-validation-message for format.
     *
     * @param array $messages Error messages as key value pairs.
     * @return Request
     */
    public function set_error_messages(array $messages = [])
    {
        $this->validation->setMessages($messages);

        return $this;
    }

    /**
     * Set the validation rules.
     *
     * @since  1.0.0
     * @see    https://github.com/rakit/validation#available-rules for format.
     *
     * @param array $rule_set Rules as key value pairs.
     * @return Request
     */
    public function set_rules(array $rule_set = [])
    {
        foreach ($rule_set as $attribute_key => $rules) {
            $this->validation->addAttribute($attribute_key, $rules);
        }

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
        $this->validation->setAliases($aliases);
    }

    /**
     * A shorthand to send wp error or success JSON responses based on validation status.
     *
     * Will use the messages, and rules as added via set_errors()/set_messages() if no overrides are present.
     * Should be used if you need to set aliases.
     *
     * @since  1.0.0
     *
     * @param  array $rules    Optional. Rules to use. Defaults to rules set via set_rules().
     * @param  array $messages Optional. Messages to use. Defaults to rules set via set_messages().
     */
    public function validate_ajax_request(array $rules = [], array $messages = [])
    {
        // Validation is not using $this->validation so overwrite rules and messages.
        if ($rules !== []) {
            $validation = $this->validate_data($this->input->to_array(), $rules, $messages);

            if ($validation === true) {
                \wp_send_json_success('Success');
            }

            \wp_send_json_error($validation, 400);
        } else {
            if ($this->is_valid()) {
                \wp_send_json_success($this->validation->getValidatedData());
            }

            \wp_send_json_error($this->get_errors(), 400);
        }
    }

    /**
     * Validates the request using the rules and messages set on the internal validation instance.
     *
     * @since  1.0.0
     *
     * @return boolean If the validation passed or not.
     */
    public function is_valid()
    {
        $this->validation->validate();

        return !$this->validation->fails();
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
        return $this->validation->errors()->toArray();
    }

    /**
     * Manually validate some data not submitted via POST, FILES, or GET.
     *
     * @since  1.0.0
     *
     * @param  array $inputs   The array of data to validate as key value pairs.
     * @param  array $rules    The rules to run against the data.
     * @param  array $messages Messages to display when a value fails.
     * @return bool|array Returns true if data validates, or an array of error messages.
     */
    public function validate_data(array $inputs, array $rules = [], array $messages = [])
    {
        /** @var \Rakit\Validation\Validation $validation */
        $validation = Container::get('Rakit\Validation\Validator')->validate($inputs, $rules, $messages);

        if ($validation->fails()) {
            return $validation->errors()->toArray();
        }

        return true;
    }


    /**
     * Set the internal instance of the Validator.
     *
     * @since  1.0.0
     */
    private function set_validation()
    {
        $validator = Container::get('Rakit\Validation\Validator');

        $this->validation = $validator->make(
            $this->input->to_array(),
            []
        );
    }
}