<?php

namespace Snap\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Snap\Utils\Url;

class UrlTest extends TestCase
{
    public function testUnleadingSlashIt()
    {
        $strings = [
            '/test' => 'test',
            '//test' => 'test',
            '/\\/test' => 'test',
            '\\test' => 'test',
            '\\test/' => 'test/',
            '//test/\\' => 'test/\\',
            '//te/st/\\' => 'te/st/\\',
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Url::unLeadingSlashIt($string));
        }
    }

    public function testLeadingSlashIt()
    {
        $strings = [
            '/test' => '/test',
            '//test' => '/test',
            '/\\/test' => '/test',
            '\\test' => '/test',
            '\\test/' => '/test/',
            '//test/\\' => '/test/\\',
            '//te/st/\\' => '/te/st/\\',
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, Url::leadingSlashIt($string));
        }
    }
}
