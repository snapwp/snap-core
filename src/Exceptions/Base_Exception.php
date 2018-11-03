<?php

namespace Snap\Exceptions;

use Exception;

/**
 * Generic Exception which forces a message to be set.
 */
class Base_Exception extends Exception
{
    /**
     * Force $message to be set.
     *
     * @since 1.0.0
     *
     * @param string         $message  Exception message.
     * @param integer        $code     Exception code.
     * @param Exception|null $previous Previous Exception.
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
