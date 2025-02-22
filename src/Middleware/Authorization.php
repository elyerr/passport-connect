<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Credentials;

class Authorization
{
    use Credentials;

    /**
     * Basic authentication 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     */
    public function handle(Request $request, Closure $next)
    {
        $credentials = $this->credentials($request);

        try {
            $response = $this->client()
                ->request('GET', $this->env()->server . '/api/gateway/check-authentication', $credentials);

            $this->report($response);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }
        } catch (RequestException $e) {

            if (!$this->env()->module && $e->getResponse()->getStatusCode() == 401) {

                try {
                    $credentials = $this->renewCredentials($request);

                    $response = $next($request);

                    foreach ($credentials as $cookie) {
                        $response->headers->setCookie($cookie);
                    }

                    return $response;

                } catch (ServerException $e) {
                    throw new ReportError("unauthorize", 401);
                }
            }
        }
        throw new ReportError("unauthorize", 401);
    }

}
