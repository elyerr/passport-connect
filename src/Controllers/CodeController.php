<?php

namespace Elyerr\Passport\Connect\Controllers;

use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Models\PassportConnect;
use Elyerr\Passport\Connect\Traits\Config;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use view;

class CodeController extends Controller
{
    use Config;

    /**
     * @var PassportConnect
     */
    protected $passportConnect;

    /**
     * Constructor
     *
     * @param PassportConnect $passportConnect
     */
    public function __construct(PassportConnect $passportConnect)
    {
        $this->middleware('guest');
        $this->passportConnect = $passportConnect;
    }

    /**
     * Show the login view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function login()
    {
        return view('auth');
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
            'code_verifier', $code_verifier = Str::random(128)
        );

        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $code_verifier, true))
            , '='), '+/', '-_');

        //Query options to generate a code 
        $query = http_build_query([
            'client_id' => $this->env()->server_id,
            'redirect_uri' => $this->env()->host . '/callback',
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => $this->env()->prompt_mode,
            'scope' => $this->env()->scopes,
        ]);

        return redirect($this->env()->server . '/oauth/authorize?' . $query);
    }

    /**
     * Make a requests to the oauth 2 server using the code to generate valid credentials
     * @param \Illuminate\Http\Request $request
     * @throws \Elyerr\ApiResponse\Exceptions\ReportError
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        $codeVerifier = $request->session()->pull('code_verifier');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            new ReportError("Can't find the session", 400)
        );
        try {

            $response_guzzle = $this->passportConnect->http
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
            throw new ReportError(__('Unauthenticated user'), 401);
        }

        $body = $response_guzzle->getBody()->getContents();
        $data = json_decode($body);

        $access_token = $data->access_token;
        $refresh_token = $data->refresh_token;
        $expires_in = $data->expires_in;

        $cookies = [
            $this->passportConnect->storeCookie($this->passportConnect->jwt_token, $access_token, ($expires_in / 60)),
            $this->passportConnect->storeCookie($this->passportConnect->jwt_refresh, $refresh_token, (30 * 24 * 60)),
        ];

        $response = $this->passportConnect->encrypt($cookies);

        $response->headers->set('Location', $this->env()->redirect_after_login);
        $response->setStatusCode(Response::HTTP_FOUND);
        return $response->send();
    }
}
