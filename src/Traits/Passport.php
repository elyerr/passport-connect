<?php

namespace Elyerr\Passport\Connect\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\ClientException;
use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Credentials;

trait Passport
{
    use Credentials;

    /**
     * Checking the scope for current user
     * @param mixed $scope
     * @return bool
     */
    public function userCan($scope)
    {
        $credentials = $this->credentials(request());
        $credentials['headers']['X-SCOPE'] = $scope;

        try {
            $response = $this->client()->get($this->env()->server . '/api/gateway/token-can', $credentials);
            if ($response->getStatusCode() === 200) {
                return true;
            }

        } catch (ClientException $e) {

            throw_unless($e->getCode() === 403, new ReportError($e->getMessage(), $e->getCode()));

            return false;
        }
        return false;
    }

    /**
     * Get the current user 
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
     */
    public function user()
    {
        $credentials = $this->credentials(request());

        try {

            $response = $this->client()->get($this->env()->server . '/api/gateway/user', $credentials);

            throw_unless($response->getStatusCode() === 200, new ReportError($response->getBody(), $response->getStatusCode()));

            return json_decode($response->getBody());

        } catch (ClientException $e) {
            if (!$this->isProduction()) {
                throw new ReportError("Request error: " . $e->getMessage(), $e->getCode());
            }

            throw new ReportError(__("Unable to retrieve user information."), $e->getCode());
        }
    }

    /**
     *  Logout session only when the module is false.
     * @param \Illuminate\Http\Request $request
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     */
    public function logout(Request $request)
    {
        $credentials = $this->credentials($request);

        $logoutEndpoint = $this->env()->server . '/api/gateway/logout';

        try {
            $response = $this->client()->post($logoutEndpoint, $credentials);

            throw_unless(
                $response->getStatusCode() === 200,
                new ReportError($response->getBody(), $response->getStatusCode())
            );

            return json_decode($response->getBody());
        } catch (ClientException $e) {
            if (!$this->isProduction()) {
                throw new ReportError("Logout request error: " . $e->getMessage(), $e->getCode());
            }

            throw new ReportError("Logout process failed. Please try again.", $e->getCode());
        }
    }


    /**
     * Get the current user 
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return mixed
     */
    public function access()
    {
        $credentials = $this->credentials(request());

        try {

            $response = $this->client()->get($this->env()->server . '/api/gateway/access', $credentials);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody());
            }

        } catch (ClientException $e) {
            if (!$this->isProduction()) {
                throw new ReportError("Request error: " . $e->getMessage(), $e->getCode());
            }

            if ($this->isProduction() && $e->getCode() == 401) {
                throw new ReportError(__("Unauthorized access. Authentication failed."), 401);
            }

            throw new ReportError(__("An unexpected error occurred. Please try again later."), $e->getCode());
        }
    }
}
