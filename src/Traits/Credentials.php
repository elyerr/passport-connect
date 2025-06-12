<?php

namespace Elyerr\Passport\Connect\Traits;

use GuzzleHttp\Client;
use Illuminate\Http\Request; 
use Illuminate\Cookie\CookieValuePrefix; 
use Elyerr\Passport\Connect\Traits\Config;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\Encryption\DecryptException;


trait Credentials
{
    use Config;

    /**
     * Guzzle instances
     * @return Client
     */
    public function client()
    {
        return new Client([
            'verify' => false
        ]);
    }

    /**
     * Get Encrypter
     * @return Encrypter
     */
    public function getEncrypter()
    {
        return app(Encrypter::class);
    }

    /**
     * Retrieve and decrypt the cookie
     * @param \Illuminate\Http\Request $request
     * @param mixed $name
     * @return array|string|null
     */
    public function getCookie(Request $request, $name)
    {
        if ($request->hasCookie($name)) {
            $value = $request->cookie($name);
            try {
                return CookieValuePrefix::remove(
                    $this->getEncrypter()->decrypt(
                        $value,
                        EncryptCookies::serialized($name)
                    )
                );
            } catch (DecryptException $e) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Retrieve the JWT token
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function jwtToken(Request $request)
    {
        return $this->getCookie($request, $this->env()->jwt_token);
    }

    /**
     * Get credentials Authorization header and cookies
     * @param \Illuminate\Http\Request $request
     * @return array<array<array|string|null>|string|null>
     */
    public function credentials(Request $request)
    {
        $data = [];
        $data['headers']['Accept'] = 'Application/json';

        $token = $this->jwtToken($request) ?? $request->bearerToken();
        
        if (!empty($token)) {
            $data['headers']['Authorization'] = str_contains('Bearer', $token) ? $token : "Bearer {$token}";
        }

        return $data;
    }


    /**
     * Create a new cookie
     * @param mixed $name
     * @param mixed $value
     * @param mixed $timeExpires 
     * @return Cookie
     */
    public function storeCookie($name, $value, $timeExpires)
    {
        return Cookie(
            $name,
            $value,
            $timeExpires ?? (60 * 60 * 24),
            $this->env()->cookie->path,
            $this->env()->cookie->domain ?? $_SERVER['HTTP_HOST'],
            $this->env()->cookie->secure,
            $this->env()->cookie->http_only,
            false,
            $this->env()->cookie->same_site
        );
    }

    /**
     * Error response
     * @return string[]
     */
    public function errorCodes()
    {
        return [
            '400' => 'Bad request',
            '401' => 'Unauthenticated',
            '404' => 'Not found',
            '403' => 'Unauthorized',
            '406' => 'Not acceptable',
        ];

    }

    /**
     * Report errors
     * @param mixed $response
     * @return void
     */
    public function report($response)
    {
        collect($this->errorCodes())->map(function ($description, $code) use ($response) {
            if ($response->getStatusCode() > 299 && $response->getStatusCode() === $code) {
                throw new ReportError($description, $code);
            }
        });
    }

    /**
     * Generate credential token and refresh token to regenerate credentials
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return Cookie
     */
    public function generateCredentials(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body);

        $access_token = $data->access_token;
        $expires_in = $data->expires_in;

        return $this->storeCookie($this->env()->jwt_token, $access_token, $expires_in);
    }

    /**
     * Check the production mode for laravel applications
     * @return bool|string
     */
    public function isProduction()
    {
        return app()->environment('production');
    }
}
