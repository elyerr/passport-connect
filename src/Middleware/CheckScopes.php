<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Credentials;

class CheckScopes
{
    use Credentials;
    /**
     * Checking credential and all scopes for the user
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed $scopes
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $scopes)
    {
        $credentials = $this->credentials($request);
        $credentials['headers']['X-SCOPES'] = $scopes;

        try {
            $response = $this->client()
                ->get($this->env()->server . '/api/gateway/check-scopes', $credentials);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }

            $this->report($response);

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
