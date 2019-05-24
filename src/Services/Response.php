<?php

namespace Snap\Services;

/**
 * Allow static access to the Response service.
 *
 * @method static  void view($view, $data = [])
 * @method static  void redirect($url, $status = 302)
 * @method static  void redirectToAdmin($path = null, $status = 302)
 * @method static  void redirectToLogin($redirect_after = null, $status = 302)
 * @method static  \Snap\Http\Response setHeader(string $name, string $value)
 * @method static  \Snap\Http\Response withHeaders(array $headers = [])
 * @method static  \Snap\Http\Response removeHeader(...$names)
 * @method static  \Snap\Http\Response setStatus(int $code, string $description)
 * @method static  \Snap\Http\Response setCookie(string $name, $value = '', int $expires = 3600, string $path = '/', string $domain = null, bool $secure = null, bool $httponly = true )
 * @method static  \Snap\Http\Response removeCookie(string $name)
 * @method static  \Snap\Http\Response set404()
 * @method static  void sendJsonSuccess($data = null, $status_code = null)
 * @method static  void sendJsonError($data = null, $status_code = null)
 *
 * @see \Snap\Http\Response
 */
class Response extends ServiceFacade
{
    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName()
    {
        return \Snap\Http\Response::class;
    }
}
