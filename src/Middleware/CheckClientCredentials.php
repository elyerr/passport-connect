<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Credentials;

class CheckClientCredentials
{
    use Credentials;

    /**
     * Checking credentials and client credentials
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
                ->request('GET', $this->env()->server . '/api/gateway/check-client-credentials', $credentials);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }

            $this->report($response);

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
