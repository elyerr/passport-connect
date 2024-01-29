<?php

namespace Elyerr\Passport\Connect\Models;

use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class PassportConnect
{
    use Config;
    /**
     * identificador del usurio privado o usuario confidencial
     * @var String
     */
    public $server_id;

    /**
     * clave secreta del usuario privado o usuario confidencial
     * @var String
     */
    public $server_key;

    /**
     * Token jwt propocionado por el servidor principal
     * @var String
     */
    public $jwt_token;

    /**
     * Token de refresh para intercambiar cuando el token twt ha vencido
     * este se usa con el grant_type refresh_token
     */
    public $jwt_refresh;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Guzzle variable
     *
     * @var \GuzzleHttp\Client
     */
    public $http;

    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
        $this->http = new Client(['verify' => false,]);
        $this->server_id = $this->env()->ids->server_id;
        $this->server_key = $this->env()->ids->server_key;
        $this->jwt_token = $this->env()->ids->jwt_token;
        $this->jwt_refresh = $this->env()->ids->jwt_refresh;

    }

    /**
     * recupera el token jwt generado por el servidor
     *
     * @param \Illuminate\Http\Request $request
     * @return String
     */
    public function jwtToken(Request $request)
    {
        return $this->getCookie($request, $this->jwt_token);
    }

    /**
     * recupera el refresh_token
     *
     *storeCookie
     * @param \Illuminate\Http\Request $request
     * @return String
     */
    public function jwtRefresh(Request $request)
    {
        return $this->getCookie($request, $this->jwt_refresh) ?: null;
    }

    /**
     * recupera el id del cliente cuando es un cliente confidencial
     *
     * @param \Illuminate\Http\Request $request
     * @return String
     */
    public function serverId(Request $request)
    {
        return $this->getCookie($request, $this->server_id) ?: $this->env()->server_id;
    }

    /**
     * recupera el secret del cliente cuando este es cliente confidencial
     *
     * @param \Illuminate\Http\Request $request
     * @return String
     */
    public function serverKey(Request $request)
    {
        return $this->getCookie($request, $this->server_key) ?: null;
    }

    /**
     * Obtine la cookie y la desencripta en el proceso o devulve la cookie
     * si esta no esta encripatada
     *
     * @param Request $request
     * @param String $name
     * @return String
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
     * crea una nueva instancia de una cookie
     *
     * @param String $name
     * @param String $value
     * @param Int $timeExpires
     * @param Boolean $http_only
     * @return \Symfony\Component\HttpFoundation\Cookie
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
     * encripta las cookies y las envia en las respuestas al cliente
     *
     * @param Cookies|Array $cookies
     * @return  Response
     */
    public function encrypt($cookies)
    {
        $response = new Response(null, 201);

        if (is_array($cookies)) {
            foreach ($cookies as $cookie) {
                if ($cookie->getName() != $this->jwt_token) {
                    $response->withCookie($this->duplicate(
                        $cookie,
                        $this->encrypter->encrypt(
                            CookieValuePrefix::create($cookie->getName(),
                                $this->encrypter->getKey()) . $cookie->getValue(),
                            EncryptCookies::serialized($cookie->getName())
                        )
                    ));
                } else {
                    $response->withCookie($this->storeCookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(), false
                    ));
                }
            }

            return $response;
        }
        /**
         * cuando no es un array de cookies
         */
        if ($cookies->getName() == $this->jwt_token) {
            return $response->withCookie($cookies);
        }

        $response->withCookie($this->duplicate(
            $cookies,
            $this->encrypter->encrypt(
                CookieValuePrefix::create($cookies->getName(),
                    $this->encrypter->getKey()) . $cookies->getValue(),
                EncryptCookies::serialized($cookies->getName())
            )
        ));
        return $response->sendHeaders();
    }

    /**
     * descripcion de errores
     *
     * @return Array|mixed
     */
    public function errorCodes()
    {
        return [
            '400' => 'no se puede procesar la solicitud tiene una sitaxis invalida',
            '401' => 'credenciales incorrectas, solicite unas validas',
            '404' => 'el servidor no pudo encontrar el recurso que buscaba',
            '403' => 'no tiene los permisos correspondientes para realizar esta operacion',
            '406' => 'su solicitud tiene credenciales correctas pero el servidor se reusa a procesar',
        ];

    }

    /**
     * busca el token jwt por medio de la cookies o por la cabecera Authorization
     *
     * @return String
     */
    public function credentials(Request $request)
    {
        $token = $this->jwtToken($request);

        return [
            'Authorization' => $token ? "Bearer " . $token : $request->header('Authorization'),
        ];
    }

    /**
     * reporta errores
     *
     * @param  $response
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
     * renova las credenciales si existe un refresh token
     *
     * @param  Request $request
     * @return mixed
     */
    public function renewCredentials($request)
    {
        throw_unless($this->jwtRefresh($request),
            new ReportError('El refresh token no pudo ser localizado', 401));

        $form = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->jwtRefresh($request),
            'client_id' => $this->serverId($request),
        ];

        if ($this->serverKey($request)) {
            $form['client_secret'] = $this->serverKey($request);
        }

        try {

            $response_guzzle = $this->http
                ->request('POST', $this->env()->server . '/api/oauth/token', [
                    'form_params' => $form,
                ]);

        } catch (ClientException $e) {
            throw new ReportError('error al actualizar las credenciales', 401);
        }

        $expires_in = json_decode($response_guzzle->getBody(), true)['expires_in'];

        $cookies = [
            $this->storeCookie($this->jwt_token, json_decode($response_guzzle->getBody(), true)['access_token'], ($expires_in / 60)),
            $this->storeCookie($this->jwt_refresh, json_decode($response_guzzle->getBody(), true)['refresh_token'], (100 * 24 * 60)),
        ];

        return $cookies;

    }

    /**
     * reemplaza una cookie en el response.
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie  $cookie
     * @param  mixed  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function duplicate(Cookie $cookie, $value)
    {
        return $this->storeCookie($cookie->getName(), $value, $cookie->getExpiresTime());
    }

    /**
     * redirecciona cuando un usuario no esta authenticado o no se puede
     * generar nuevas credenciales
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\Response
     */
    public function isNotAuthenticable($request, $response)
    {
        if ($response->getStatusCode() == 401) {

            $credentials = $this->renewCredentials($request);

            if (count($credentials) == 0) {
                header("Location: " . $this->env()->login);
                exit;
            }

            $response = $this->encrypt($credentials);

            if (!$request->wantsJson()) {

                $response->headers->set('Location', $this->getUri());
                $response->setStatusCode(Response::HTTP_FOUND);
                return $response->send();

            }

            $response->setStatusCode(Response::HTTP_CREATED);
            return $response->setContent('Credenciales actualizadas');
        }
    }

    /**
     * obtiene la uri actual
     *
     * @return String
     */
    public function getUri()
    {
        $dominio = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $protocolo = 'http://';

        if (isset($_SERVER['HTTPS'])) {
            $protocolo = 'https://';
        }

        return $protocolo . $dominio . $uri;
    }
}
