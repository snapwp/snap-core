<?php

namespace Snap\Http\Validation;

use Rakit\Validation\Rule as ValidationRule;

/**
 * Simple extension to Rakit\Validation\Rule to keep within the Snap namespace.
 */
class Rule extends ValidationRule
{
    /**
     * See if the input passes this rule or not.
     *
     * @since  1.0.0
     *
     * @param  mixed $input The $input to validate.
     * @return bool whether tha $input passed or failed.
     */
    public function check($input): bool
    {
        return $this->handle($input);
    }
}
