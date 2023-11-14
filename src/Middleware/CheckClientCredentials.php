<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Elyerr\Passport\Connect\Models\PassportConnect;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class CheckClientCredentials extends PassportConnect
{
    public function handle(Request $request, Closure $next, $scopes)
    {
        $authorization = $this->credentials($request);
 
        try {
            $response = $this->http
                ->request('GET', $this->env()->server . '/api/gateway/check-client-credentials', [
                    'headers' => [
                        'Authorization' => $authorization,
                        'Scopes' => $scopes,
                    ],
                ]);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }

            $this->report($response);

        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == 401) {
                return $this->isNotAuthenticable($request, $e->getResponse());
            }
        }
    }
}
