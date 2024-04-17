<?php

namespace Snap\Http\Validation\Traits;

use \Somnambulist\Components\Validation\ErrorBag;

trait ValidatesInput
{
    /**
     * Can be overloaded to provide default rules to the Validation instance.
     *
     * @return array Array of rules.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Can be overloaded to provide default error messages to the Validation instance.
     *
     * @return array Array of messages.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Can be overloaded to provide default aliases to the Validation instance.
     *
     * @return array Array of messages.
     */
    protected function aliases(): array
    {
        return [];
    }
}
