<?php

namespace Elyerr\Passport\Connect\Middleware;

use Closure;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Models\PassportConnect;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class Authorization extends PassportConnect
{   
    /**
     * Checking the credentials is valid
     * @param mixed $request
     * @param \Closure $next
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $credentials = $this->credentials($request);

        try {
            $response = $this->http
                ->request('GET', $this->env()->server . '/api/gateway/check-authentication', [
                    'headers' => $credentials,
                ]);

            $this->report($response);

            if ($response->getStatusCode() == 200) {
                return $next($request);
            }
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                try {
                    return $this->isNotAuthenticatable($request, $e->getResponse());
                } catch (ServerException $e) {
                    throw new ReportError("Can't update credentials", 401);
                }
            }
        }
    }

}
