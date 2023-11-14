<?php

use Illuminate\Support\Str;

return [

    /**
     * Variable que determina la ubicacion principal del servidor de authorizacion
     * esta variable de entorno si tienes laravel puedes configurarlo en el archivo env
     * en caso contrario puedes remplazar su contenido con la url del servidor
     */
    'server' => env('SERVER') ?: 'localhost',

    /**
     * Indentificador del server id del microservicio que se le asigno, este ID
     * lo genera el servidor de autorizacion, esta clave solo sera necesario cuando el cliente es
     * publico
     */
    'server_id' => env('SERVER_ID')? : null,
 
    /**
     * Variable donde se manejara las credenciales de los usuarios, estas variables 
     * no es necesario cambiarlas, pero si lo haces todas deben tener un nombre distinto
     */
    'ids' => [
        'server_id' => Str::slug(env('APP_NAME', 'passport'), '_') . '_id_outh2_server',
        'server_key' => Str::slug(env('APP_NAME', 'passport'), '_') . '__key_outh2_server',
        'jwt_token' => Str::slug(env('APP_NAME', 'passport'), '_') . '_outh2_server',
        'jwt_refresh' => Str::slug(env('APP_NAME', 'passport'), '_') . '_refresh_outh2_server',
        'csrf_refresh' => Str::slug(env('APP_NAME', 'passport'), '_') . '_csrf_refresh_outh2_server',
    ],

    /**
     * ruta donde estara ubicado el login en tu aplicacion, eres libre de modificarlo 
     * dependiendo de la configuracion de tu microservicio
     */
    'login' => '/login',

    /**
     * Pagina a donde debe ser redireccionado luego que se hayan
     * genereado las credenciales en el la ruta /callback , debes ajustar el valor 
     * a dependiendo de la configuracion de tu microservicio
     */
    'redirect_after_login' => '/',

    /**
     * Nombre de la Cookies donde se almacenara las el id de la session
     * que se utilizara para poder recuperarla en cualquier momento
     */
    'session' => Str::slug(env('APP_NAME') ?: 'passport', '_') . '_connect_outh2_server',

    /**
     * Configuracion para la creacion de cookies, no es necesario cambiar la configuracion
     * pero puedes ajustarla a tu conveniencia
     */
    'cookie' => [
        'path' => '/',
        'domain' => config('session.domain') ?: 'localhost',
        'time_expires' => 10,
        'secure' => isset($_SERVER['HTTPS']) ? true : false,
        'http_only' => isset($_SERVER['HTTPS']) ? false : true,
        'same_site' => 'lax',
    ],

    /**
     * Configuracion de redis que funciona para almacenar sesiones del plugin
     * independientemente de la sesion de la aplicacion, puedes remplazar
     * los datos por valores de tu instacia de redis de tu servidor
     */
    'redis' => [
        'schema' => 'tcp',
        'host' => env('REDIS_HOST') ?: '127.0.0.1',
        'port' => env('REDIS_PORT') ?: '6379',
        'prefix' => 'passport',
    ],
];
