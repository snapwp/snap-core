<?php

namespace Snap\Request;

use Rakit\Validation\Rule as ValidationRule;

class Rule extends ValidationRule
{
    public function check($input)
    {
        return $this->handle($input);
    }
}
