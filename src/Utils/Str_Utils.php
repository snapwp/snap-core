<?php

namespace Snap\Utils;

/**
 * Provides some basic string utilities.
 */
class Str_Utils
{
    /**
     * Transform a string into valid snake case.
     *
     * @since 1.0.0
     *
     * @param string $string The string to convert.
     * @return string
     */
    public static function to_snake($string)
    {
        if (\ctype_lower($string)) {
            return $string;
        }

        $string = \preg_replace('/\s+/u', '', \ucwords($string));

        $string = \trim(\preg_replace('/([^_])(?=[A-Z])/', '$1_', $string), '_');

        return \strtolower($string);
    }
}
