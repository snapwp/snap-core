<?php

namespace Snap\Http\Validation\Traits;

trait ValidatesInput
{
    /**
     * A helper method to quickly set rules and messages.
     *
     * @param array $rule_set Array of rules to set.
     * @param array messages Error messages as key value pairs.
     * @return $this
     */
    public function validate(array $rule_set = [], array $messages = [])
    {
        $this->setRules($rule_set);
        $this->setErrorMessages($messages);
        return $this;
    }

    /**
     * Can be overloaded to provide default rules to the Validation instance.
     *
     * @return array Array of rules.
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Can be overloaded to provide default error messages to the Validation instance.
     *
     * @return array Array of messages.
     */
    protected function messages()
    {
        return [];
    }

    /**
     * Can be overloaded to provide default aliases to the Validation instance.
     *
     * @return array Array of messages.
     */
    protected function aliases()
    {
        return [];
    }
}
