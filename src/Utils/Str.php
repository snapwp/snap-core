<?php

namespace Snap\Utils;

use Bladezero\Support\Str as BladeStr;

/**
 * Provides some basic string utilities.
 */
class Str
{
    /**
     * Studly cache.
     *
     * @var array
     */
    private static $studly_cache = [];

    /**
     * Camel cache.
     *
     * @var array
     */
    private static $camel_cache = [];

    /**
     * Snake cache.
     *
     * @var array
     */
    private static $snake_cache = [];

    /**
     * Transform a string into valid snake case.
     *
     * @param string $string The string to convert.
     * @return string
     */
    public static function toSnake(string $string): string
    {
        if (isset(static::$snake_cache[$string])) {
            return static::$snake_cache[$string];
        }
        if (\ctype_lower($string)) {
            return $string;
        }

        $string = \preg_replace('/-+/u', ' ', $string);
        $string = \preg_replace('/\s+/u', '', \ucwords($string));

        static::$snake_cache[$string] = \strtolower(
            \trim(
                \preg_replace('/([^_])(?=[A-Z])/', '$1_', $string),
                '_'
            )
        );

        return static::$snake_cache[$string];
    }

    /**
     * Converts a string to StudlyCase.
     *
     * @param string $string String to studly.
     * @return string
     */
    public static function toStudly(string $string): string
    {
        if (isset(static::$studly_cache[$string])) {
            return static::$studly_cache[$string];
        }

        static::$studly_cache[$string] = \str_replace(['_', '-', ' '], '', \ucwords($string, " \t\r\n\f\v_-"));
        return static::$studly_cache[$string];
    }

    /**
     * Converts a string to camelCase.
     *
     * @param string $string String to camel.
     * @return string
     */
    public static function toCamel(string $string): string
    {
        if (isset(static::$camel_cache[$string])) {
            return static::$camel_cache[$string];
        }

        static::$camel_cache[$string] = \lcfirst(static::toStudly($string));
        return static::$camel_cache[$string];
    }

    /**
     * Pluralize the provided string.
     *
     * @param string $string String to pluralize.
     * @return string
     */
    public static function toPlural(string $string): string
    {
        return BladeStr::plural($string);
    }

    /**
     * Singularize a plural string.
     *
     * @param string $string String to singularize.
     * @return string
     */
    public static function toSingular(string $string): string
    {
        return BladeStr::singular($string);
    }
}
