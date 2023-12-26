<?php

namespace Elyerr\Passport\Connect\Traits;

use Elyerr\Passport\Connect\Traits\Config;
use Illuminate\Support\Facades\Http;

trait Passport
{

    use Config;

    /**
     * verifica que el cliente a traves de un usuario verifique si cuenta
     * con permisos correcto antes de ejecutar la accion
     * @param String $scope
     * @return bool
     */
    public function userCan($scope)
    {
        $request = request();
        $cookie = $request->cookie($this->env()->ids->jwt_token);

        $response = Http::withHeaders([
            'Authorization' => "Bearer $cookie",
            'X-SCOPE' => $scope,
        ])->get($this->env()->server . '/api/gateway/token-can');
    
        return $response->status() === 200 ? true : false;
    }
}
