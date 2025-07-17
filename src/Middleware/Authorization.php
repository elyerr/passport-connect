<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Elyerr\Passport\Connect\Support\Response;
use Exception;
use Elyerr\Passport\Connect\Http\Client;
use Elyerr\Passport\Connect\Traits\Config;
use GuzzleHttp\Exception\RequestException;

class Authorization
{
    use Config;

    /**
     * Client HTTP
     * @var Client
     */
    public $client;

    /**
     * Host oauth2 passport server
     * @var 
     */
    public $uri;

    /**
     * Construct
     * @param \Elyerr\Passport\Connect\Http\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->uri = 'api/gateway/check-authentication';
    }

    /**
     * Verify basic authentication
     * @param  mixed $request
     * @param \Closure|null $next
     * @throws \Exception
     */
    public function handle($request, Closure $next = null)
    {
        try {
            $response = $this->client->get($this->uri);

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

            return $next ? $next($request) : true;

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;

            throw new Exception($e->getMessage(), $statusCode);
        }
    }
}
