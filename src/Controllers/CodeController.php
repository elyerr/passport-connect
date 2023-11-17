<?php

namespace Elyerr\Passport\Connect\Controllers;

use Elyerr\ApiResponse\Exceptions\ReportError;
use Elyerr\Passport\Connect\Models\PassportConnect;
use Elyerr\Passport\Connect\Models\Session;
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

    protected $session;
    /**
     * Constructor de la clase.
     *
     * @param PassportConnect $passportConnect
     */
    public function __construct(PassportConnect $passportConnect)
    {
        $this->session = new Session($passportConnect);
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
        $cookies = $this->session->startSession();

        $response = $this->passportConnect->encrypt($cookies);

        return $response->setContent(view('auth'));
    }

    /**
     * Redirecciona para solicitar un código de autorización.
     *
     * @param Request $request
     */
    public function redirect(Request $request)
    {
        /**
         * creamos una session
         */
        $state = Str::random(40);
        $this->session->setKey($request, 'state', $state);

        /**
         * creamos una propiedad que contendra el code_verifier, el cual
         * se intercambiara con el server de authorizacion
         */
        $code_verifier = Str::random(128);
        $this->session->setKey($request, 'code_verifier', $code_verifier);

        // Generar el código challenge
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $code_verifier, true))
            , '='), '+/', '-_');

        // Crear la URL con los parámetros para la solicitud
        $query = http_build_query([
            'client_id' => env('SERVER_ID'),
            'redirect_uri' => env('APP_URL') . '/callback',
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        header('Location: ' . env('SERVER') . '/grant-access?' . $query);
        exit;

    }

    /**
     * Permite intercambiar el código generado en primera instancia con un token JWT
     * y generar cookies de sesión.
     *
     * @param Request $request
     */
    public function callback(Request $request)
    {
        /**
         * get paramas
         */
        $data = explode('?', $request->state);

        /**
         * csrf token enviado desde el cliente
         */
        $transport_state = $data[0];

        /**
         * identificador unico del cliente
         */
        $client_id = str_replace('id=', '', $data[1]);

        /**
         * token temporal valido por 10 Segundos despues de ese tiempo
         * la peticion va ser invalida
         */
        $csrf = str_replace('csrf=', '', $data[2]);

        $state = $this->session->getKey($request, 'state');

        $codeVerifier = $this->session->getKey($request, 'code_verifier');

        throw_unless(
            strlen($state) > 0 && $state === $transport_state,
            new ReportError("La session no ha sido encontrada", 400)
        );
        try {

            $response_guzzle = $this->passportConnect->http
                ->post(env('SERVER') . '/api/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => $client_id,
                        'redirect_uri' => env('APP_URL') . '/callback',
                        'code_verifier' => $codeVerifier,
                        'code' => $request->code,
                    ],
                    'headers' => [
                        'X-CSRF-TOKEN' => $csrf,
                    ],
                ]);
        } catch (ClientException $e) {
            throw new ReportError("El servidor rechazo la conexion", 401);

        }

        // Obtener los valores del encabezado y el cuerpo de la respuesta
        $x_csrf_refresh = $response_guzzle->getHeader('X-CSRF-REFRESH')[0];
        $body = $response_guzzle->getBody()->getContents();
        $responseData = json_decode($body, true);

        $access_token = $responseData['token_type'] . " " . $responseData['access_token'];
        $refresh_token = $responseData['refresh_token'];
        $expires_in = $responseData['expires_in'];

        // creacion de cookies
        $cookies = [
            $this->passportConnect->storeCookie($this->passportConnect->jwt_token, $access_token, ($expires_in / 60)),
            $this->passportConnect->storeCookie($this->passportConnect->jwt_refresh, $refresh_token, (30 * 24 * 60)),
            $this->passportConnect->storeCookie($this->passportConnect->csrf_refresh, $x_csrf_refresh, (30 * 24 * 60)),
        ];
        $response = $this->passportConnect->encrypt($cookies);

        $response->headers->set('Location', $this->env()->redirect_after_login);
        $response->setStatusCode(Response::HTTP_FOUND);
        return $response->send();

    }

}
