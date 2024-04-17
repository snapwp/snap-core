<?php

namespace Snap\Http\Validation;

use Somnambulist\Components\Validation\ErrorBag;
use Snap\Services\Container;

/**
 * A handy wrapper around Somnambulist\Components\Validation.
 */
class Validator
{
    /**
     * Holds the ErrorBag instance.
     *
     * @var ErrorBag
     */
    public static ErrorBag $errors;

    /**
     * Holds the validation instance.
     *
     * @var \Somnambulist\Components\Validation\validation
     */
    protected \Somnambulist\Components\Validation\validation $validation;


    /**
     * Setup the validation instance.
     */
    public function make(array $data = [], array $rules = [], array $messages = []): static
    {
        if ($data !== null) {
            $this->validation = Container::get('validationFactory')->make(
                $data,
                $rules
            );
        }

        if (!empty($messages)) {
            $this->setMessages($messages);
        }

        return $this;
    }

    /**
     * Set the validation error messages.
     */
    public function setMessages(array $messages = [], string $lang = 'en'): static
    {
        $this->validation->messages()->add($lang, $messages);
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
    public function setAliases(array $aliases = []): static
    {
        foreach ($aliases as $original => $alias) {
            $this->validation->setAlias($original, $alias);
        }
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
        return !$this->validation->fails();
    }

    /**
     * Get errors from the internal validation instance as a multi-dimensional array with numerical indexes.
     * Shortcut to errors()->get($key, $format).
     *
     * Calling with no arguments returns a numerically index array of inputs and their errors - great for AJAX
     * responses.
     *
     * @param null $key Optional. The key to search for. EG. 'name' or 'uploads.*'.
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
     */
    public function errors(): ErrorBag
    {
        return $this->validation->errors();
    }

    /**
     * Return all data which had a validation rule run against it.
     */
    public function getValidatedData(): array
    {
        return $this->validation->getValidatedData();
    }

    /**
     * Return all data which had a validation rule run against it and passed.
     */
    public function getValidData(): array
    {
        return $this->validation->getValidData();
    }

    /**
     * Return all data which had a validation rule run against it and failed.
     */
    public function getInvalidData(): array
    {
        return $this->validation->getInvalidData();
    }
}
