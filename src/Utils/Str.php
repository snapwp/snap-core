<?php

namespace Snap\Utils;

use Doctrine\Common\Inflector\Inflector;

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
    private static $studlyCache = [];

    /**
     * Camel cache.
     *
     * @var array
     */
    private static $camelCache = [];

    /**
     * Snake cache.
     *
     * @var array
     */
    private static $snakeCache = [];

    /**
     * Transform a string into valid snake case.
     *
     * @param string $string The string to convert.
     * @return string
     */
    public static function toSnake(string $string): string
    {
        if (isset(static::$snakeCache[$string])) {
            return static::$snakeCache[$string];
        }
        if (\ctype_lower($string)) {
            return $string;
        }

        $string = \preg_replace('/\-+/u', ' ', $string);
        $string = \preg_replace('/\s+/u', '', \ucwords($string));

        static::$snakeCache[$string] = \strtolower(
            \trim(
                \preg_replace('/([^_])(?=[A-Z])/', '$1_', $string),
                '_'
            )
        );

        return static::$snakeCache[$string];
    }

    /**
     * Converts a string to StudlyCase.
     *
     * @param string $string String to studly.
     * @return string
     */
    public static function toStudly(string $string): string
    {
        if (isset(static::$studlyCache[$string])) {
            return static::$studlyCache[$string];
        }

        static::$studlyCache[$string] = \str_replace(['_', '-', ' '], '', \ucwords($string, " \t\r\n\f\v_-"));
        return static::$studlyCache[$string];
    }

    /**
     * Converts a string to camelCase.
     *
     * @param string $string String to camel.
     * @return string
     */
    public static function toCamel(string $string): string
    {
        if (isset(static::$camelCache[$string])) {
            return static::$camelCache[$string];
        }

        static::$camelCache[$string] = \lcfirst(static::toStudly($string));
        return static::$camelCache[$string];
    }

    /**
     * Pluralize the provided string.
     *
     * @param string $string String to pluralize.
     * @return string
     */
    public static function toPlural(string $string): string
    {
        return Inflector::pluralize($string);
    }

    /**
     * Singularize a plural string.
     *
     * @param string $string String to singularize.
     * @return string
     */
    public static function toSingular(string $string): string
    {
        return Inflector::singularize($string);
    }
}
