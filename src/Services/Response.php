<?php

namespace Snap\Services;

/**
 * Allow static access to the Response service.
 *
 * @method static  redirect($url, $status = 302)
 * @method static  redirect_to_admin($path = null, $status = 302)
 * @method static  redirect_to_login($redirect_after = null, $status = 302)
 * @method static  set_404()
 * @method static  send_json_success($data, $status_code = null)
 * @method static  send_json_error($data, $status_code = null)
 *
 * @see \Snap\Http\Response
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
