<?php

namespace Snap\Http\Validation;

use Somnambulist\Components\Validation\Rule as ValidationRule;

abstract class Rule extends ValidationRule
{
    /**
     * Run the child class's handle method.
     */
    public function check(mixed $value): bool
    {
        return $this->handle($value);
    }

    /**
     * See if the input passes this rule or not.
     */
    abstract protected function handle($value): bool;
}
