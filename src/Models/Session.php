<?php

namespace Elyerr\Passport\Connect\Models;

use Predis\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Elyerr\Passport\Connect\Traits\Config;
use Symfony\Component\HttpFoundation\Cookie;
use Elyerr\Passport\Connect\Models\PassportConnect;

class Session
{
    use Config;

    /**
     * @var \Predis\Client
     */
    protected $client;

    /**
     * @var Elyerr\Passport\Connect\Models\PassportConnect
     */
    protected $passport;

    /**
     *
     */
    public function __construct(PassportConnect $passport)
    {
        $this->passport = $passport;

        $this->client = new Client([
            'scheme' => $this->env()->redis->schema,
            'host' => $this->env()->redis->host,
            'port' => $this->env()->redis->port,
            'databse' => $this->env()->redis->database
        ]);
    }

    /**
     * Obtiene el identificador original de la sesion
     * 
     * @param Request $request
     * @return String
     */
    public function getUUID(Request $request)
    {
        return $this->env()->redis->prefix . '_' . $request->cookie($this->env()->session) . ':';
    }

    /**
     * Crea una nueva clave para la session actual
     * 
     * @param Request $request
     * @param String $key
     * @param String $value
     * @return void
     */
    public function setKey(Request $request, $key, $value)
    {
        $key = $this->getUUID($request) . "$key";

        $this->client->set($key, $value);
    }

    /**
     * Inicia la sesion alamacenando a traves de redis y creando una cookie
     * que almacenara el id de la sesion
     *
     * @return Cookie
     */
    public function startSession()
    {
        $value = Str::random(20);
        $key = $this->env()->redis->prefix . "_$value:id";

        $this->client->set($key, $value);

        $cookie = $this->passport->storeCookie(
            $this->env()->session,
            $value,
            $this->env()->cookie->time_expires
        );

        return $cookie;
    }

    /**
     * obtiene el valor de una clave de la session actual
     * 
     * @param Request $request
     * @param String $key
     * @return String
     */
    public function getKey(Request $request, $key)
    {
        $key = $this->getUUID($request) . "$key";

        return $this->client->get($key);
    }
}
