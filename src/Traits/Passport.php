<?php

namespace Elyerr\Passport\Connect\Traits;

use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

trait Passport
{

    use Config;

    /**
     * cliente de guzzle
     */
    public function client()
    {
        return new Client(['verify' => false]);
    }

    /**
     * verifica que el cliente a traves de un usuario verifique si cuenta
     * con permisos correcto antes de ejecutar la accion
     * @param String $scope
     * @return bool
     */
    public function userCan($scope)
    {
        $request = request();
        $cookie = $request->cookie($this->env()->ids->jwt_token);

        try {

            $this->client()
                ->get($this->env()->server . '/api/gateway/token-can', [
                    'headers' => [
                        'X-SCOPE' => $scope,
                        'Authorization' => $cookie ? "Bearer $cookie" : $request->header('Authorization'),
                    ],
                ]);

            return true;

        } catch (ClientException $e) {
            
            throw_unless($e->getCode() === 403, new ReportError($e->getMessage(), $e->getCode()));

            return false;
        }
    }

    /**
     * retorna al usuario authenticado
     *
     */
    public function user()
    {
        $request = request();
        $cookie = $request->cookie($this->env()->ids->jwt_token);

        try {

            $response = $this->client()
                ->get($this->env()->server . '/api/gateway/user', [
                    'headers' => [
                        'Authorization' => $cookie ? "Bearer $cookie" : $request->header('Authorization'),
                    ],
                ]);

            throw_unless($response->getStatusCode() === 200, new ReportError($response->getBody(), $response->getStatusCode()));

            return json_decode($response->getBody());

        } catch (ClientException $e) {

            throw new ReportError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * envia una notificacion, recibe como parametro un array de opciones, para que se
     * puedan enviar notificaciones se debe agregar VERIFY_NOTIFICATION en la variable de
     * entorno en el archivo .env, la data dentro del array debe contener los siguiente
     * via: [array] | valores['database','email']
     * subject: [string]
     * message: [string]
     * users: [string] | valores['email', '*','scope']
     *
     * @param Array $form
     */
    public function send_notification(array $form)
    {
        $request = request();
        $cookie = $request->cookie($this->env()->ids->jwt_token);

        try {
            $this->client()
                ->post($this->env()
                        ->server . '/api/gateway/send-notification', [
                        'headers' => [
                            'X-VERIFY-NOTIFICATION' => $this->env()->verify_notification,
                            'Authorization' => $cookie ? "Bearer $cookie" : $request->header,
                        ],
                        'form_params' => $form,
                    ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }
}
