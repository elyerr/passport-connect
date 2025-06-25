<?php

namespace Elyerr\Passport\Connect\Support;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class CookieManager
{

    /**
     * Request
     * @var Request
     */
    protected Request $request;

    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?: Request::createFromGlobals();
    }

    /**
     * Get a cookie value by name
     * @param string $name
     * @param mixed $default
     * @return bool|float|int|string|null
     */
    public function get(string $name, $default = null)
    {
        return $this->request->cookies->get($name, $default);
    }

    /**
     * Create a cookie object to attach to a response
     * @param string $name
     * @param string $value
     * @param string $domain
     * @param int $minutes
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return Cookie
     */
    public static function make(
        string $name,
        string $value,
        string $domain,
        int $minutes = 60,
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'lax'
    ): Cookie {
        return Cookie::create($name)
            ->withValue($value)
            ->withExpires(strtotime("+{$minutes} minutes"))
            ->withPath('/')
            ->withDomain($domain)
            ->withSecure($secure)
            ->withHttpOnly($httpOnly)
            ->withSameSite($sameSite);
    }

    /**
     * Expire/delete a cookie
     * @param string $name
     * @return Cookie
     */
    public static function forget(string $name): Cookie
    {
        return Cookie::create($name)->withExpires(1);
    }
}
