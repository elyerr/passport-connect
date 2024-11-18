<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Models\PassportConnect;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckScopes extends PassportConnect
{
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
        $credentials['Scopes'] = $scopes;

        try {
            $response = $this->http
                ->get($this->env()->server . '/api/gateway/check-scopes', [
                    'headers' => $credentials,
                ]);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }

            $this->report($response);

        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == 401) {
                try {
                    return $this->isNotAuthenticable($request, $e->getResponse());
                } catch (ServerException $e) {
                    throw new ReportError("Can't update credentials", 401);
                }
            }
        }
    }
}
