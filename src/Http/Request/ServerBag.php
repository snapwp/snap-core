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
                    return \strtoupper(\filter_var($method, FILTER_SANITIZE_STRING));
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
            'SERVER_NAME' => FILTER_SANITIZE_STRING,
            'HTTP_HOST' => FILTER_SANITIZE_URL,
            'HTTP_REFERER' => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => FILTER_SANITIZE_STRING,
        ];
        
        $server = \filter_input_array(INPUT_SERVER, $definition);

        if ('' !== \preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $server['HTTP_HOST'])) {
            \wp_die('This site has been temporarily disabled due to suspicious activity');
        }

        $this->data = $server;
    }
}
