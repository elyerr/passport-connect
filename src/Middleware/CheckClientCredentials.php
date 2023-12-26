<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Models\PassportConnect;

class CheckClientCredentials extends PassportConnect
{
    public function handle(Request $request, Closure $next, $scopes)
    {
        $credentials = $this->credentials($request);
        $credentials['Scopes'] = $scopes;

        try {
            $response = $this->http
                ->request('GET', $this->env()->server . '/api/gateway/check-client-credentials', [
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
