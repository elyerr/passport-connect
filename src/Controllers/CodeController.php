<?php

namespace Elyerr\Passport\Connect\Controllers;

use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Traits\Credentials;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class CodeController extends Controller
{
    use Credentials;

    /**
     * Constructor
     * 
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the login view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function login()
    {
        return view(view: 'auth');
    }

    /**
     * Make redirect action to generate a code response
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $request->session()->put(
            'code_verifier',
            $code_verifier = Str::random(128)
        );

        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $code_verifier, true))
            ,
            '='
        ), '+/', '-_');

        //Query options to generate a code 
        $query = http_build_query([
            'client_id' => $this->env()->server_id,
            'redirect_uri' => $this->env()->host . '/callback',
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => $this->env()->prompt_mode
        ]);

        return redirect($this->env()->server . '/oauth/authorize?' . $query);
    }

    /**
     * Make a requests to the oauth 2 server using the code to generate valid credentials
     * @param \Illuminate\Http\Request $request
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        $codeVerifier = $request->session()->pull('code_verifier');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            new ReportError("Can't find the session", 404)
        );

        try {

            $response = $this->client()
                ->post($this->env()->server . '/api/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => $this->env()->server_id,
                        'redirect_uri' => $this->env()->host . '/callback',
                        'code_verifier' => $codeVerifier,
                        'code' => $request->code,
                    ],
                ]);
        } catch (ClientException $e) {
            throw new ReportError(__('Unauthenticated'), 401);
        }

        $jwtToken = $this->generateCredentials($response);

        $redirect_to = "{$this->env()->host}/{$this->env()->redirect_after_login}";

        return redirect($redirect_to)->withCookie($jwtToken);
    }
}
