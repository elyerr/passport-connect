<?php

namespace Elyerr\Passport\Connect\Traits;

use ErrorException;

trait Config
{
    /**
     * Load the configuration file in both Laravel and PHP environments.
     * @throws \RuntimeException
     * @return object
     */
    public function env(): object
    {
        $path = __DIR__ . '/../../config/config.php';

        try {
            $config = require $path;
            return json_decode(json_encode($config));
        } catch (ErrorException $e) {
            throw new \RuntimeException("Cannot load the configuration file: {$path}");
        }
    }
}
