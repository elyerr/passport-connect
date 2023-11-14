<?php

namespace Elyerr\Passport\Connect\Traits;

trait Config
{
    public function env()
    {
        return json_decode(json_encode(require __DIR__ . "/../../config/config.php"));
    }
}
