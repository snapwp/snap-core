<?php

namespace Snap\Http\Validation;

use Rakit\Validation\Rule as ValidationRule;

class Rule extends ValidationRule
{
    /**
     * See if the input passes this rule or not.
     *
     * @param  mixed $input The $input to validate.
     * @return bool whether tha $input passed or failed.
     */
    public function check($input): bool
    {
        return $this->handle($input);
    }
}
