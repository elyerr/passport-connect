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
     * Guzzle instances
     * @return Client
     */
    public function client()
    {
        return new Client(['verify' => false]);
    }

    /**
     * Checking the scope for current user
     * @param mixed $scope
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
     * Get the current user 
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
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
     * Send notifications
     * @param array $form
     * @return bool
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
