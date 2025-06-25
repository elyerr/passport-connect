<?php

namespace Elyerr\Passport\Connect\Controllers;

use Exception;
use Elyerr\Passport\Connect\Http\Client;
use Elyerr\Passport\Connect\Http\Request;
use Elyerr\Passport\Connect\Traits\Config;
use Elyerr\Passport\Connect\Support\CookieManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;


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
    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->session = new Session(
            new NativeSessionStorage(
                [
                    'name' => $this->env()->jwt_token,
                    'cookie_domain' => $this->env()->cookie->domain,
                ]
            )
        );
    }

    /**
     * Make redirect action to generate a code response
     * @param \Elyerr\Passport\Connect\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(20));
        $verifier = bin2hex(random_bytes(64));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $this->session->set('state', $state);
        $this->session->set('verifier', $verifier);

        //Query options to generate a code 
        $query = http_build_query([
            'client_id' => $this->env()->server_id,
            'redirect_uri' => $this->env()->host . '/callback',
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'prompt' => $this->env()->prompt_mode
        ]);

        return new RedirectResponse($this->env()->server . '/oauth/authorize?' . $query);
    }

    /**
     * Make a requests to the oauth 2 server using the code to generate valid credentials
     * @param \Elyerr\Passport\Connect\Http\Request $request
     * @throws \Exception
     * @return RedirectResponse
     */
    public function callback(Request $request)
    {
        $state = $this->session->get('state');
        $verifier = $this->session->get('verifier');

        if ($state !== $request->input('state')) {
            throw new Exception("Invalid state.");
        }

        try {
            $uri = $this->env()->server . '/api/oauth/token';

            $response = $this->client->post($uri, [
                'grant_type' => 'authorization_code',
                'client_id' => $this->env()->server_id,
                'redirect_uri' => $this->env()->host . '/callback',
                'code_verifier' => $verifier,
                'code' => $request->input('code'),
            ]);

            $redirect_to = "{$this->env()->host}/{$this->env()->redirect_after_login}";

            $token = CookieManager::make(
                $this->env()->jwt_token,
                $response->data->access_token,
                $this->env()->cookie->domain,
                $response->data->expires_in
            );

            $redirect = new RedirectResponse($redirect_to);
            $redirect->headers->setCookie($token);

            return $redirect;


        } catch (Exception $e) {
            throw new Exception(__('Unauthenticated'), 401);
        }
    }
}
