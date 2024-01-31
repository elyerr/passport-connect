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
     * Constructor de la clase.
     *
     * @param PassportConnect $passportConnect
     */
    public function __construct(PassportConnect $passportConnect)
    {
        $this->middleware('guest');
        $this->passportConnect = $passportConnect;
    }

    /**
     * Muestra la vista de inicio de sesión cuando el usuario no está autorizado.
     * esta configuracion esta pensada para funconar bajo laravel si en tu proyecto
     * no usas laravel puedes adecuarla para que renorne la vista
     *
     * @return View
     */
    public function login()
    {
        return view('auth');
    }

    /**
     * Redirecciona para solicitar un código de autorización.
     *
     * @param Request $request
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

        // Crear la URL con los parámetros para la solicitud
        $query = http_build_query([
            'client_id' => $this->env()->server_id,
            'redirect_uri' => $this->env()->host . '/callback',
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => $this->env()->prompt_mode,
            'scope' => implode(' ', $this->env()->scopes),
        ]);

        return redirect($this->env()->server . '/oauth/authorize?' . $query);

    }

    /**
     * Permite intercambiar el código generado en primera instancia con un token JWT
     * y generar cookies de sesión.
     *
     * @param Request $request
     */
    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        $codeVerifier = $request->session()->pull('code_verifier');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            new ReportError("La session no ha sido encontrada", 400)
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
            throw new ReportError("El servidor rechazo la conexion", 401);

        }

        // Obtener los valores del encabezado y el cuerpo de la respuesta
        $body = $response_guzzle->getBody()->getContents();
        $data = json_decode($body);

        $access_token = $data->token_type . " " . $data->access_token;
        $refresh_token = $data->refresh_token;
        $expires_in = $data->expires_in;

        // creacion de cookies
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
