<?php

namespace Snap\Tests\Utils;

use Snap\Utils\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testToCamel()
    {
        $strings = [
            'test' => 'test',
            'mixed stuff' => 'mixedStuff',
            'really really  long string to test' => 'reallyReallyLongStringToTest',
            '_this_has_underscores' => 'thisHasUnderscores',
            '__mixed--stuff_' => 'mixedStuff',
            'mixed-- _stuff' => 'mixedStuff',
            'MixedStuff' => 'mixedStuff',
            'basic-kebab' => 'basicKebab',
            '_is_snake_' => 'isSnake'
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Str::toCamel($string));
        }
    }

    public function testToSnake()
    {
        $strings = [
            'test' => 'test',
            'mixed stuff' => 'mixed_stuff',
            'really really  long string to test' => 'really_really_long_string_to_test',
            '_this_has_underscores' => 'this_has_underscores',
            '__mixed--stuff_' => 'mixed_stuff',
            'mixed-- _stuff' => 'mixed_stuff',
            'MixedStuff' => 'mixed_stuff',
            'basic-kebab' => 'basic_kebab',
            '_is_snake_' => 'is_snake'
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Str::toSnake($string));
        }
    }

    public function testToStudly()
    {
        $strings = [
            'test' => 'Test',
            'mixed stuff' => 'MixedStuff',
            'really really  long string to test' => 'ReallyReallyLongStringToTest',
            '_this_has_underscores' => 'ThisHasUnderscores',
            '__mixed--stuff_' => 'MixedStuff',
            'mixed-- _stuff' => 'MixedStuff',
            'MixedStuff' => 'MixedStuff',
            'basic-kebab' => 'BasicKebab',
            '_is_snake_' => 'IsSnake'
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Str::toStudly($string));
        }
    }

    /**
     * Not testing these specifically as Doctrine/Inflector handles their own tests.
     */
    public function testToPlural()
    {
        $strings = [
            'job' => 'jobs',
            'potato' => 'potatoes',
            'some dog' => 'some dogs',
            'this_has_underscore' => 'this_has_underscores',
            'this-has-hyphen' => 'this-has-hyphens',
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Str::toPlural($string));
        }
    }

    /**
     * Not testing these specifically as Doctrine/Inflector handles their own tests.
     */
    public function testToSingular()
    {
        $strings = [
            'jobs' => 'job',
            'potatoes' => 'potato',
            'some dogs' => 'some dog',
            'this_has_underscores' => 'this_has_underscore',
            'this-has-hyphens' => 'this-has-hyphen',
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Str::toSingular($string));
        }
    }
}
