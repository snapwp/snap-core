<?php

namespace Snap\Services;

/**
 * Allow static access to the Request service.
 *
 * @method static null|string getMethod()
 * @method static null|string getUrl()
 * @method static null|string getScheme()
 * @method static null|string getPath()
 * @method static null|string getHost()
 * @method static null|string getIp()
 * @method static array getPathSegments()
 * @method static string getMatchedQuery()
 * @method static string getMatchedRule()
 * @method static bool isMobile()
 * @method static bool isMethod($method)
 * @method static \Snap\Http\Request\Bag|mixed input(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed post(string $key = null, $default = null)
 * @method static \Snap\Http\Request\FileBag|mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[] files(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed server(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed wp(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed cookie(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed query(string $key = null, $default = null)
 * @method static \Snap\Http\Request\Bag|mixed route(string $key = null, $default = null)
 * @method static bool has($key)
 * @method static bool hasFile($key)
 * @method static bool hasInput()
 * @method static bool filled($key)
 * @method static bool isLoginPage()
 * @method static bool isPostTemplate($post_template)
 * @method static bool getGlobalErrors()
 * @method static \Snap\Http\Request getRootInstance() Return root instance.
 *
 * @see \Snap\Http\Request
 */
class Request
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Http\Request::class;
    }
}
