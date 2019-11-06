<?php

namespace Snap\Utils;

use Snap\Services\Request;

class Url
{
    /**
     * Check if a URL is off-site or not.
     *
     * @param string $url The URL to check.
     * @return bool
     */
    public static function isExternalUrl(string $url): bool
    {
        if (\parse_url($url, PHP_URL_HOST) === Request::getHost()) {
            return false;
        }

        if (\strpos($url, "/") === 0) {
            return false;
        }

        return true;
    }

    /**
     * Ensures a string begins with a slash.
     *
     * @param string $string
     * @return string
     */
    public static function leadingSlashIt(string $string): string
    {
        return '/' . self::unLeadingSlashIt($string);
    }

    /**
     * Removes any leading slashes from a string.
     *
     * @param string $string String to remove leading slashes for.
     * @return string
     */
    public static function unLeadingSlashIt(string $string): string
    {
        return \ltrim($string, '/\\');
    }
}
