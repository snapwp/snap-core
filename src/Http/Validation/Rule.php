<?php

namespace Snap\Http\Validation;

use Rakit\Validation\Rule as ValidationRule;

abstract class Rule extends ValidationRule
{
    /**
     * Run the child class's handle method.
     *
     * @param  mixed $input The $input to validate.
     * @return bool whether that $input passed or failed.
     */
    public function check($input): bool
    {
        return $this->handle($input);
    }

    /**
     * See if the input passes this rule or not.
     *
     * @param $input
     * @return bool
     */
    abstract protected function handle($input): bool;
}
