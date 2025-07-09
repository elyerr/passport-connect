<?php
namespace Elyerr\Passport\Connect\Services;

use Exception;
use Elyerr\Passport\Connect\Http\Client;
use Elyerr\Passport\Connect\Http\Request;
use Elyerr\Passport\Connect\Traits\Config;
use Elyerr\Passport\Connect\Support\Response;
use Elyerr\Passport\Connect\Http\SessionManager;
use Elyerr\Passport\Connect\Support\CookieManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Passport
{
    use Config;

    /**
     * Client HTTP
     * @var Client
     */
    private $client;


    /**
     * Session manager
     * @var SessionManager
     */
    private $session;


    /**
     * Construct
     * @param \Elyerr\Passport\Connect\Http\Client $client 
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Checking scope for the user authorization can do an action
     * @param string $scope
     * @throws \Exception
     * @return  JsonResponse|void
     */
    public function userCan(string $scope)
    {
        $headers = ['X-SCOPE' => $scope];
        $this->client->addHeaders($headers);

        try {
            $response = $this->client->get('api/gateway/token-can');

            if ($response->status != 200) {

                if ($response->status == 403) {
                    return Response::json([
                        "message" => "Unauthorized access."
                    ], $response->status);
                }

                return Response::json([
                    "message" => "Authentication is failed."
                ], $response->status);
            }
        } catch (Exception $e) {
            return Response::json([
                "message" => $e->getMessage()
            ], $response->status);
        }
    }

    /**
     * Get the current user 
     * @return  JsonResponse|\stdClass
     */
    public function user(): JsonResponse|\stdClass
    {
        try {

            $response = $this->client->get('api/gateway/user');

            if ($response->status != 200) {

                if ($response->status == 403) {
                    return Response::json([
                        "message" => "Unauthorized access."
                    ], $response->status);
                }

                return Response::json([
                    "message" => "Authentication is failed."
                ], $response->status);
            }

            return (Object) $response->data;

        } catch (Exception $e) {

            return Response::json([
                "message" => $e->getMessage()
            ], $response->status);
        }
    }

    /**
     * Logout
     * @param \Elyerr\Passport\Connect\Http\Request $request
     * @throws \Exception
     * @return JsonResponse|\stdClass
     */
    public function logout(Request $request): JsonResponse|\stdClass
    {
        try {
            $response = $this->client->post('api/gateway/logout');

            if ($response->status != 200) {

                if ($response->status == 403) {
                    return Response::json([
                        "message" => "Unauthorized access."
                    ], $response->status);
                }

                return Response::json([
                    "message" => "Authentication is failed."
                ], $response->status);
            }

            return (Object) $response->data;

        } catch (Exception $e) {
            return Response::json([
                "message" => $e->getMessage()
            ], $response->status);
        }
    }


    /**
     * Retrieve the all scopes for the authenticated user
     * @return JsonResponse|\stdClass
     */
    public function access(): JsonResponse|\stdClass
    {
        try {

            $response = $this->client->get('api/gateway/access');

            if ($response->status != 200) {

                if ($response->status == 403) {
                    return Response::json([
                        "message" => "Unauthorized access."
                    ], $response->status);
                }

                return Response::json([
                    "message" => "Authentication is failed."
                ], $response->status);
            }

            return (Object) $response->data;

        } catch (Exception $e) {

            return Response::json([
                "message" => $e->getMessage()
            ], $response->status);
        }
    }
}
