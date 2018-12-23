<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static  redirect($url, $status = 302)
 * @method static  redirect_to_admin($path = null, $status = 302)
 * @method static  redirect_to_login($redirect_after = null, $status = 302)
 * @method static  set_404()
 *
 * @see \Snap\Core\Router
 */
class Response extends Service_Facade
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
        return \Snap\Http\Response::class;
    }
}
