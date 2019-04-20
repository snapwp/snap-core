<?php

namespace Snap\Services;

/**
 * Allow static access to the Request service.
 *
 * @method static string get_method()
 * @method static string get_url()
 * @method static string get_scheme()
 * @method static string get_path()
 * @method static string get_host()
 * @method static array get_path_segments()
 * @method static bool is_method($method)
 * @method static mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[] get($key, $default = null)
 * @method static mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[] post($key, $default = null)
 * @method static mixed|\Snap\Http\Request\File\File|\Snap\Http\Request\File\File[] files($key, $default = null)
 * @method static mixed server($key, $default = null)
 * @method static mixed query($key, $default = null)
 * @method static bool has($key)
 * @method static bool has_file($key)
 * @method static bool filled($key)
 * @method static bool is_wp_login()
 * @method static bool is_post_template($post_template)
 *
 * @see \Snap\Http\Request
 */
class Request extends Service_Facade
{
    /**
     * Specify the underlying root class.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function get_service_name()
    {
        return \Snap\Http\Request::class;
    }
}
