<?php

namespace Elyerr\Passport\Connect\Traits;

use ErrorException;

trait Config
{   
    /**
     * Set de conf file
     * @return mixed
     */
    public function env()
    {
        try {
            return json_decode(json_encode(require base_path('config/passport_connect.php')));
            
        } catch (ErrorException $e) {
            
            return json_decode(json_encode(require __DIR__ . "/../../config/config.php"));
        }
    }
}
