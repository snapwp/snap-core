<?php

namespace Snap\Http\Validation;

use Rakit\Validation\ErrorBag;
use Snap\Services\Container;

/**
 * A handy wrapper around Rakit\Validation.
 */
class Validation
{
    /**
     * Holds the ErrorBag instance.
     *
     * @var \Rakit\Validation\ErrorBag
     */
    public static $errors;

    /**
     * Holds the validation instance.
     *
     * @var \Rakit\Validation\validation
     */
    protected $validation;

    /**
     * Validation constructor.
     *
     * @param null|array $data     Optional. Input data to validate.
     * @param array      $rules    Optional. Validation rules array.
     * @param array      $messages Optional. Validation messages.
     */
    public function __construct($data = null, array $rules = [], array $messages = [])
    {
        if ($data !== null) {
            $this->validation = Container::get('Rakit\Validation\Validator')->make(
                $data,
                []
            );
        }

        if (!empty($rules)) {
            $this->setRules($rules);
        }

        if (!empty($messages)) {
            $this->setErrorMessages($messages);
        }

        // TODO THESE WILL BE POPULATED EVEN WHEN VALIDATING SINGLE ARRAY. IDEALLY ONLY WHEN WORKING ON REQUESTS
        static::$errors = $this->validation->errors();
    }

    // TODO WORK OUT A WAY TO VALIDATE SINGLE ARRAYS ETC

    /**
     * Set the validation error messages.
     *
     * @see    https://github.com/rakit/validation#custom-validation-message for format.
     *
     * @param array $messages Error messages as key value pairs.
     * @return $this
     */
    public function setErrorMessages(array $messages = [])
    {
        $this->validation->setMessages($messages);
        return $this;
    }

    /**
     * Set the validation rules.
     *
     * @see    https://github.com/rakit/validation#available-rules for format.
     *
     * @param array $rule_set Rules as key value pairs.
     * @return $this
     */
    public function setRules(array $rule_set = [])
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
     * @param array $aliases Key value pairs as original => alias.
     * @return $this
     */
    public function setAliases(array $aliases = [])
    {
        $this->validation->setAliases($aliases);
        return $this;
    }

    /**
     * Optionally set translations for any built-in error messages.
     *
     * @param array $translations Translations to set.
     * @return $this
     */
    public function setTranslations(array $translations)
    {
        $this->validation->setTranslations($translations);
        return $this;
    }

    /**
     * Validates the request using the rules and messages set on the internal validation instance.
     *
     * @return boolean If the validation passed or not.
     */
    public function isValid(): bool
    {

        $this->validation->validate();
        static::$errors = $this->validation->errors();
        return !$this->validation->fails();
    }

    /**
     * Get errors from the internal validation instance as a multi-dimensional array with numerical indexes.
     * Shortcut to errors()->get($key, $format).
     *
     * Calling with no arguments returns a numerically index array of inputs and their errors - great for AJAX
     * responses.
     *
     * @param null   $key    Optional. The key to search for. EG. 'name' or 'uploads.*'.
     * @param string $format Optional. Format of the returned errors.
     *                       Defaults to :message.
     * @return array
     */
    public function getErrors($key = null, string $format = ':message'): array
    {
        if ($key !== null) {
            return $this->validation->errors()->get($key, $format);
        }

        return \array_map(
            function ($values) {
                return \array_values($values);
            },
            $this->validation->errors()->toArray()
        );
    }

    /**
     * Helper method to get count of all errors. Shortcut to errors()->count().
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->validation->errors()->count();
    }

    /**
     * Returns a flat array of all errors. Shortcut to errors()->all().
     *
     * @param string $format Optional. Format to wrap errors in such as '<li>:message</li>'.
     *                       Defaults to ':message'.
     * @return array
     */
    public function getAllErrors(string $format = ':message'): array
    {
        return $this->validation->errors()->all($format);
    }

    /**
     * Checks if an error exists. Shortcut to errors()->has().
     *
     * @param string $key The key to search for. EG. 'name' or 'uploads.*'.
     * @return bool
     */
    public function hasErrors(string $key): bool
    {
        return $this->validation->errors()->has($key);
    }

    /**
     * Return the underlying Validation instance's ErrorBag.
     *
     * @return \Rakit\Validation\ErrorBag
     */
    public function errors(): ErrorBag
    {
        return $this->validation->errors();
    }

    public function getValidatedData()
    {
        return $this->validation->getValidatedData();
    }

    public function getValidData()
    {
        return $this->validation->getValidData();
    }

    public function getInvalidData()
    {
        return $this->validation->getInvalidData();
    }
}
