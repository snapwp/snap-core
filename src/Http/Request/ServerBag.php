<?php

namespace Snap\Http\Request;

/**
 * Server parameter bag.
 */
class ServerBag extends Bag
{
    /**
     * Populate the server bag.
     *
     * @param array $contents Kept for compatibility.
     */
    protected function setData(array $contents = [])
    {
        $definition = [
            'REQUEST_METHOD' => [
                'filter' => FILTER_CALLBACK,
                'options' => function ($method) {
                    return \strtoupper(static::sanitizeString($method));
                },
            ],
            'QUERY_STRING' => FILTER_UNSAFE_RAW,
            'REQUEST_URI' => FILTER_SANITIZE_URL,
            'REMOTE_ADDR' => FILTER_VALIDATE_IP,
            'HTTP_X_FORWARDED' => FILTER_VALIDATE_IP,
            'HTTP_X_FORWARDED_FOR' => FILTER_VALIDATE_IP,
            'HTTP_CLIENT_IP' => FILTER_VALIDATE_IP,
            'HTTP_X_CLUSTER_CLIENT_IP' => FILTER_VALIDATE_IP,
            'HTTP_FORWARDED_FOR' => FILTER_VALIDATE_IP,
            'HTTP_FORWARDED' => FILTER_VALIDATE_IP,
            'SERVER_PORT' => FILTER_SANITIZE_NUMBER_INT,
            'SERVER_NAME' => [
                'filter' => FILTER_CALLBACK,
                'options' => [static::class, 'sanitizeString'],
            ],
            'HTTP_HOST' => FILTER_SANITIZE_URL,
            'HTTP_REFERER' => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => [
                'filter' => FILTER_CALLBACK,
                'options' => [static::class, 'sanitizeString'],
            ],
        ];
        
        $server = \filter_input_array(INPUT_SERVER, $definition);

        if ('' !== \preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $server['HTTP_HOST'] ?? '')) {
            \wp_die('This site has been temporarily disabled due to suspicious activity');
        }

        $this->data = $server;
    }

    /**
     * Replacement for the deprecated FILTER_SANITIZE_STRING filter.
     *
     * @param string $value The value to sanitize.
     * @return string
     */
    public static function sanitizeString($value): string
    {
        return \str_replace(["'", '"'], ['&#39;', '&#34;'], \strip_tags((string) $value));
    }
}
