<?php

namespace Snap\Utils;

/**
 * Provides some basic string utilities.
 */
class Str
{
    /**
     * Transform a string into valid snake case.
     *
     * @param string $string The string to convert.
     * @return string
     */
    public static function toSnake(string $string): string
    {
        if (\ctype_lower($string)) {
            return $string;
        }

        $string = \preg_replace('/\s+/u', '', \ucwords($string));

        $string = \trim(\preg_replace('/([^_])(?=[A-Z])/', '$1_', $string), '_');

        return \strtolower($string);
    }
}
