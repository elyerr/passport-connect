<?php

namespace Elyerr\Passport\Connect\Controllers;

use Exception;
use Elyerr\Passport\Connect\Http\Client;
use Elyerr\Passport\Connect\Http\Request;
use Elyerr\Passport\Connect\Traits\Config;
use Elyerr\Passport\Connect\Http\SessionManager;
use Elyerr\Passport\Connect\Support\CookieManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CodeController 
{

    use Config;

    /**
     * Session
     * @var 
     */
    protected $session;

    /**
     * Client HTTP
     * @var Client
     */
    protected $client;


    /**
     * Constructor
     * 
     */
    public function __construct(SessionManager $sessionManager, Client $client)
    {
        $this->session = $sessionManager;

        $this->client = $client;
    }

    /**
     * Make redirect action to generate a code response
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        $state = bin2hex(random_bytes(20));
        $this->session->set('state', $state);

        $data = [
            'response_type' => 'code',
            'client_id' => $this->env()->client_id,
            'redirect_uri' => $this->env()->host . '/callback',
            'state' => $state,
            'prompt' => $this->env()->prompt_mode
        ];

        //pcke
        if (empty($this->env()->client_secret)) {
            $verifier = bin2hex(random_bytes(64));
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $this->session->set('verifier', $verifier);

            $data['code_challenge'] = $challenge;
            $data['code_challenge_method'] = 'S256';
        }

        //Query options to generate a code 
        $query = http_build_query($data);
        return new RedirectResponse($this->env()->server . '/oauth/authorize?' . $query);
    }

    /**
     * Make a requests to the oauth 2 server using the code to generate valid credentials
     * @param \Elyerr\Passport\Connect\Http\Request $request
     * @throws \Exception
     * @return RedirectResponse
     */
    public function callback(Request $request): RedirectResponse
    {

        $state = $this->session->get('state');

        if ($state !== $request->input('state')) {
            throw new Exception("Invalid state.");
        }

        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->env()->client_id,
            'client_secret' => $this->env()->client_secret,
            'redirect_uri' => $this->env()->host . '/callback',
            'code' => $request->input('code'),
        ];

        //pcke
        if (empty($this->env()->client_secret)) {
            unset($data['client_secret']);

            $verifier = $this->session->get('verifier');
            $data['code_verifier'] = $verifier;
        }

        try {
            $uri = $this->env()->server . '/api/oauth/token';

            $response = $this->client->post($uri, $data);

            return $this->response($response, $this->session);

        } catch (Exception $e) {

            if ($e->getCode() == 401) {
                return $this->regenerateSession($this->session);
            }

            throw new Exception(__('Unauthenticated'), 401);
        }
    }


    /**
     * Regenerate session
     * @return RedirectResponse
     */
    public function regenerateSession(SessionManager $sessionManager)
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $sessionManager->get('refresh_token'),
            'client_id' => $this->env()->client_id,
        ];

        if ($this->env()->client_secret) {
            $data['client_secret'] = $this->env()->client_secret;
        }

        $uri = $this->env()->server . '/api/oauth/token';

        try {
            $response = $this->client->post($uri, $data);
            return $this->response($response, $this->session);

        } catch (Exception $e) {
            throw new Exception(__('Unauthenticated'), 401);
        }
    }

    /**
     * Generate redirect response
     * @param mixed $response
     * @param \Elyerr\Passport\Connect\Http\SessionManager $sessionManager
     * @return RedirectResponse
     */
    public function response($response, SessionManager $sessionManager)
    {
        $redirect_to = "{$this->env()->host}/{$this->env()->redirect_after_login}";

        $sessionManager->set('cookie_lifetime', $response->data->expires_in);
        $sessionManager->set('access_token', $response->data->access_token);
        $sessionManager->set('refresh_token', $response->data->refresh_token);

        $token = CookieManager::make(
            $this->env()->jwt_token,
            $sessionManager->getId(),
            $this->env()->cookie->domain,
            $response->data->expires_in,
            $this->env()->cookie->secure,
            $this->env()->cookie->http_only,
            $this->env()->cookie->same_site,
        );

        $redirect = new RedirectResponse($redirect_to);
        $redirect->headers->setCookie($token);

        return $redirect;
    }
}
