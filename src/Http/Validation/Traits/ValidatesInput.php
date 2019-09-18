<?php

namespace Snap\Http\Validation\Traits;

trait ValidatesInput
{
    /**
     * Holds the global ErrorBag instance for use in templates.
     *
     * @var \Rakit\Validation\ErrorBag
     */
    protected static $globalErrors;

    /**
     * Returns the ErrorBag being used by the current request.
     *
     * @return \Rakit\Validation\ErrorBag
     */
    public static function getGlobalErrors()
    {
        return static::$globalErrors;
    }

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
        $this->setMessages($messages);
        return $this;
    }

    /**
     * Validates the request using the rules and messages set on the internal validation instance.
     *
     * @return boolean If the validation passed or not.
     */
    public function isValid(): bool
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->validation->validate();

        /** @noinspection PhpUndefinedMethodInspection */
        static::$globalErrors = $this->validation->errors();

        /** @noinspection PhpUndefinedMethodInspection */
        return !$this->validation->fails();
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
