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
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;

            if ($statusCode === 401) {
                throw new ReportError(__("Unauthorized access. Authentication failed."), 401);
            }

            if (!$this->isProduction()) {
                throw new ReportError("Request error: " . $e->getMessage(), $statusCode ?? 500);
            }
        }

        throw new ReportError(__("Unauthorized access. Authentication is required."), 401);
    }

}
