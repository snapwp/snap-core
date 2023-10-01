<?php

namespace Snap\Http\Request;

/**
 * Server parameter bag.
 */
class Server_Bag extends Bag
{
    /**
     * Populate the server bag.
     *
     * @since 1.0.0
     *
     * @param array $contents Kept for compatibility.
     */
    protected function set_data(array $contents = []): void
    {
        $definition = [
            'REQUEST_METHOD' => [
                'filter' => FILTER_CALLBACK,
                'options' => function ($method) {
                    return \strtoupper(\filter_var($method, FILTER_UNSAFE_RAW));
                },
            ],
            'QUERY_STRING' => FILTER_UNSAFE_RAW,
            'REMOTE_ADDR' => FILTER_VALIDATE_IP,
            'SERVER_PORT' => FILTER_SANITIZE_NUMBER_INT,
            'SERVER_NAME' => FILTER_UNSAFE_RAW,
            'HTTP_HOST' => FILTER_SANITIZE_URL,
            'HTTP_REFERER' => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => FILTER_UNSAFE_RAW,
        ];

        $server = \filter_input_array(INPUT_SERVER, $definition);

        if ('' !== \preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $server['HTTP_HOST'])) {
            \wp_die('This site has been temporarily disabled due to suspicious activity');
        }

        $this->data = $server;
    }
}
