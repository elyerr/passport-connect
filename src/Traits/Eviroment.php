<?php

namespace Elyerr\Passport\Connect\Traits;

use Elyerr\ApiResponse\Assets\Asset;
use Illuminate\Filesystem\Filesystem;

trait Eviroment
{
    use Asset;

    /**
     * Agrega la variable de entorno del dominio del servidor
     * @return void
     */
    public function addEviromentServer()
    {
        $file = base_path('.env');

        $this->addString($file, 8, "SERVER=\n");
    }

    /**
     * Agrega una vista personalizada para mostrar errores.
     *
     * @return void
     */
    public function addErrorView()
    {
        $fs = new Filesystem();
        $DIR = base_path('resources/views/error');
        $FILE = 'report.blade.php';

        if (!file_exists($DIR)) {
            $fs->makeDirectory($DIR);
        }

        $fs->copy(__DIR__ . "/../../resources/views/error/report.blade.php", "$DIR/$FILE");

    }

    /**
     * Agrega una vista personalizada para mostrar errores.
     *
     * @return void
     */
    public function addAuthView()
    {
        $fs = new Filesystem;
        $DIR = base_path('resources/views');
        $FILE = 'auth.blade.php';

        $fs->copy(__DIR__ . "/../../resources/views/auth.blade.php", "$DIR/$FILE");
    }

    /**
     * registra los middlware en el kernel
     */
    public function addMidleware()
    {
        $middlewares = [
            "'server' => \Elyerr\Passport\Connect\Middleware\Authorization::class",
            "'scope' => \Elyerr\Passport\Connect\Middleware\CheckForAnyScope::class",
            "'scopes' => \Elyerr\Passport\Connect\Middleware\CheckScopes::class",
            "'client' => \Elyerr\Passport\Connect\Middleware\CheckClientCredentials::class",
        ];

        $kernel = base_path('app/Http/Kernel.php');

        $readFile = fopen($kernel, 'r');

        if ($readFile) {
            $index = 0;
            while (!feof($readFile)) {
                $line = fgets($readFile);
                if (strpos($line, "verified")) {
                    foreach ($middlewares as $middleware) {
                        $index += 1;
                        $this->addString($kernel, $index, "\t\t$middleware,\n");
                    }
                }
                $index += 1;
            }
        }

    }

}
