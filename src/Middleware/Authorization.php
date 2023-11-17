<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Elyerr\Passport\Connect\Models\PassportConnect;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class Authorization extends PassportConnect
{

    public function handle($request, Closure $next)
    {
        $credentials = $this->credentials($request);

        try {
            $response = $this->http
                ->request('GET', $this->env()->server . '/api/gateway/check-authentication', [
                    'headers' => $credentials
                ]);

            $this->report($response);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                return $this->isNotAuthenticable($request, $e->getResponse());
            }
        }
    }

}
