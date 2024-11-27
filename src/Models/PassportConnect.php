<?php

namespace Elyerr\Passport\Connect\Models;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Cookie\CookieValuePrefix;
use GuzzleHttp\Exception\ClientException;
use Elyerr\Passport\Connect\Traits\Config;
use Symfony\Component\HttpFoundation\Cookie;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\Encryption\DecryptException;

class PassportConnect
{
    use Config;

    /**
     * Token JWT
     * @var string
     */
    public $jwt_token;

    /**
     * Refresh token to update credentials
     * @var
     */
    public $jwt_refresh;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Guzzle
     *
     * @var \GuzzleHttp\Client
     */
    public $http;

    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
        $this->http = new Client(['verify' => false]);
        $this->jwt_token = $this->env()->ids->jwt_token;
        $this->jwt_refresh = $this->env()->ids->jwt_refresh;
    }

    /**
     * Retrieve the JWT token
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function jwtToken(Request $request)
    {
        return $this->getCookie($request, $this->jwt_token);
    }

    /**
     * Retrieve the refresh token
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    public function jwtRefresh(Request $request)
    {
        return $this->getCookie($request, $this->jwt_refresh) ?: null;
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
                    $this->encrypter->decrypt(
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
     * Create a new cookie
     * @param mixed $name
     * @param mixed $value
     * @param mixed $timeExpires
     * @param mixed $http_only
     * @return Cookie
     */
    public function storeCookie($name, $value, $timeExpires, $http_only = true)
    {
        return Cookie(
            $name,
            $value,
            $timeExpires,
            $this->env()->cookie->path,
            $this->env()->cookie->domain,
            $this->env()->cookie->secure,
            $http_only,
            false,
            $this->env()->cookie->same_site,
        );
    }

    /**
     * Encrypt the cookies and sent to the user
     * @param mixed $cookies
     * @return mixed
     */
    public function encrypt($cookies)
    {
        $response = new Response();

        if (is_array($cookies)) {
            foreach ($cookies as $cookie) {
                if ($cookie->getName() != $this->jwt_token) {
                    $response->withCookie($this->duplicate(
                        $cookie,
                        $this->encrypter->encrypt(
                            CookieValuePrefix::create(
                                $cookie->getName(),
                                $this->encrypter->getKey()
                            ) . $cookie->getValue(),
                            EncryptCookies::serialized($cookie->getName())
                        )
                    ));
                } else {
                    $response->withCookie($this->storeCookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        false
                    ));
                }
            }
        } else {
            //There is not an array
            if ($cookies->getName() == $this->jwt_token) {
                return $response->withCookie($cookies);
            }

            $response->withCookie($this->duplicate(
                $cookies,
                $this->encrypter->encrypt(
                    CookieValuePrefix::create(
                        $cookies->getName(),
                        $this->encrypter->getKey()
                    ) . $cookies->getValue(),
                    EncryptCookies::serialized($cookies->getName())
                )
            ));
        }
        return $response;
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
     * Search credentials
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function credentials(Request $request)
    {
        $token = $this->jwtToken($request);

        return [
            'Authorization' => $token ? "Bearer " . $token : $request->header('Authorization'),
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
     * Renew credentials using the refresh token
     * @param mixed $request
     * @return array
     */
    public function renewCredentials($request)
    {
        $form = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->jwtRefresh($request),
            'client_id' => $this->env()->server_id,
        ];

        $response = $this->http
            ->request('POST', $this->env()->server . '/api/oauth/token', [
                'form_params' => $form,
            ]);

        $expires_in = json_decode($response->getBody(), true)['expires_in'];

        $cookies = [
            $this->storeCookie($this->jwt_token, json_decode($response->getBody(), true)['access_token'], ($expires_in / 60)),
            $this->storeCookie($this->jwt_refresh, json_decode($response->getBody(), true)['refresh_token'], (100 * 24 * 60)),
        ];

        return $cookies;
    }

    /**
     * Replace the cookie in the response
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     * @param mixed $value
     * @return Cookie|\Illuminate\Cookie\CookieJar
     */
    protected function duplicate(Cookie $cookie, $value)
    {
        return $this->storeCookie($cookie->getName(), $value, $cookie->getExpiresTime());
    }

    /**
     * Checking authentication
     * @param mixed $request
     * @param mixed $response
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
     */
    public function isNotAuthenticatable($request, $response)
    {
        if ($response->getStatusCode() == 401) {

            try {
                $credentials = $this->renewCredentials($request);

                if (count($credentials) == 0) {
                    return redirect($this->env()->login);
                }

                $response = $this->encrypt($credentials);

                if (!$request->wantsJson()) {
                    $response->headers->set('Location', $this->env()->host);
                    $response->setStatusCode(302);
                    return $response->send();
                }

                $response->setStatusCode(201);
                return $response->setContent(204);

            } catch (ClientException $e) {

                if (request()->wantsJson()) {
                    throw new ReportError(__('Unauthenticated'), 401);
                }
                return redirect($this->env()->login);
            }
        }
    }
}
