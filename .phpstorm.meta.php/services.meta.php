<?php

namespace PHPSTORM_META {

    override(
        \Snap\Services\Container::get(0),
        map(
            [
                "response" => \Snap\Http\Response::class,
                \Snap\Http\Response => \Snap\Http\Response::class,
                "config" => \Snap\Core\Config::class,
                \Snap\Core\Config => \Snap\Core\Config::class,
                "blade" => \Snap\Templating\Blade\Factory::class,
                \Snap\Templating\Blade\Factory => \Snap\Templating\Blade\Factory::class,
                "view" => \Snap\Templating\View::class,
                \Snap\Templating\View => \Snap\Templating\View::class,
                "router" => \Snap\Routing\Router::class,
                \Snap\Routing\Router => \Snap\Routing\Router::class,
                "response" => \Snap\Http\Response::class,
                \Snap\Http\Response => \Snap\Http\Response::class,
                "request" => \Snap\Http\Request::class,
                \Snap\Http\Request => \Snap\Http\Request::class,
                "validator" => \Snap\Http\Validation\Validator::class,
                \Snap\Http\Validation\Validator => \Snap\Http\Validation\Validator::class,
                "email" => \Snap\Utils\Email::class,
                \Snap\Utils\Email => \Snap\Utils\Email::class,
                "validationFactory" => \Somnambulist\Components\Validation\Factory::class,
                \Somnambulist\Components\Validation\Factory => \Somnambulist\Components\Validation\Factory::class,

                "WP_Query" => \WP_Query::class,
                "wpdb" => \wpdb::class,

                \Snap\Database\PostQuery => \Snap\Database\PostQuery::class,
                \Snap\Database\TaxQuery => \Snap\Database\TaxQuery::class,
            ]
        )
    );

    override(
        \get_service(0),
        map(
            [
                "response" => \Snap\Http\Response::class,
                \Snap\Http\Response => \Snap\Http\Response::class,
                "config" => \Snap\Core\Config::class,
                \Snap\Core\Config => \Snap\Core\Config::class,
                "blade" => \Snap\Templating\Blade\Factory::class,
                \Snap\Templating\Blade\Factory => \Snap\Templating\Blade\Factory::class,
                "view" => \Snap\Templating\View::class,
                \Snap\Templating\View => \Snap\Templating\View::class,
                "router" => \Snap\Routing\Router::class,
                \Snap\Routing\Router => \Snap\Routing\Router::class,
                "response" => \Snap\Http\Response::class,
                \Snap\Http\Response => \Snap\Http\Response::class,
                "request" => \Snap\Http\Request::class,
                \Snap\Http\Request => \Snap\Http\Request::class,
                "validator" => \Snap\Http\Validation\Validator::class,
                \Snap\Http\Validation\Validator => \Snap\Http\Validation\Validator::class,
                "email" => \Snap\Utils\Email::class,
                \Snap\Utils\Email => \Snap\Utils\Email::class,

                "validationFactory" => \Somnambulist\Components\Validation\Factory::class,
                \Somnambulist\Components\Validation\Factory => \Somnambulist\Components\Validation\Factory::class,

                "WP_Query" => \WP_Query::class,
                "wpdb" => \wpdb::class,

                \Snap\Database\PostQuery => \Snap\Database\PostQuery::class,
                \Snap\Database\TaxQuery => \Snap\Database\TaxQuery::class,
            ]
        )
    );
}
