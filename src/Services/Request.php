<?php

namespace Snap\Services;

/**
 * Allow static access to the Request service.
 *
 * @method static  get_method()
 * @method static  get_url()
 * @method static  get_scheme()
 * @method static  get_path()
 * @method static  get_path_segments()
 * @method static  is_method($method)
 * @method static  get($key, $default = null)
 * @method static  post($key, $default = null)
 * @method static  file($key, $default = null)
 * @method static  server($key, $default = null)
 * @method static  query($key, $default = null)
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
