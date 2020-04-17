<?php

namespace Snap\Http\Validation\Rules;

use Snap\Http\Validation\Rule;

class Nonce extends Rule
{
    /**
     * The default error message when this rule is not met.
     *
     * @var string
     */
    protected $message = "You have not passed our security checks.";

    /**
     * Allowed params for this rule.
     *
     * @var array
     */
    protected $fillableParams = ['action'];

    /**
     * When true and this rule fails, then no other rules are process for the attribute.
     *
     * @var bool
     */
    protected $implicit = true;

    /**
     * Validate the $value to see if it passes the rule.
     *
     * @param mixed $value The input from the Request to validate against.
     * @return bool
     */
    protected function handle($value): bool
    {
        if (empty($value)) {
            return false;
        }

        if ($this->parameter('action') === null) {
            return (bool)\wp_verify_nonce($value);
        }

        return (bool)\wp_verify_nonce($value, $this->parameter('action'));
    }
}
