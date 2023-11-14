<?php

namespace Elyerr\Passport\Connect\Console;

use Elyerr\Passport\Connect\Traits\Eviroment;
use Illuminate\Console\Command;

class ClientConsole extends Command
{

    use Eviroment;

    /**
     * @var String
     */
    protected $routes;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "passport-connect:client-public";

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = "Configura el cliente publico";

    public function __construct()
    {
        parent::__construct();

        $this->routes = base_path('routes/web.php');

    }
    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $this->addEviromentServer();
        $this->addEviromentKey();
        $this->addErrorView();
        $this->addAuthView();
        $this->addMidleware();
        $this->uploadImport();
        $this->uploadRoutes();

        $this->info("La configuracion ha sido configurada");
    }

    /**
     * Identificador unico para el cliente, esta variable es necesaria cuando
     * se trata de un cliente confidencial
     */
    public function addEviromentKey()
    {
        $file = base_path('.env');

        $this->addString($file, 8, "SERVER_ID=\n");
    }

    /**
     * Importa el controlador para las rutas
     * @return void
     */
    public function uploadImport()
    {
        $imports = [
            'Elyerr\Passport\Connect\Controllers\CodeController;',
        ];

        $readFile = fopen($this->routes, 'r');

        $index = 0;
        if ($readFile) {
            while (!feof($readFile)) {
                $line = fgets($readFile);
                if (strpos($line, '?php')) {
                    $index += 2;
                    foreach ($imports as $value) {
                        $this->addString($this->routes, $index, "use $value\n");
                        $index += 1;
                    }
                }
            }
        }
    }

    /**
     * Registra las rutas necesarias para el cliente publico funciones
     * @return void
     */
    public function uploadRoutes()
    {
        $routes = [
            "Route::get('/login', [CodeController::class, 'login'])->name('login');",
            "Route::get('/redirect', [CodeController::class, 'redirect'])->name('redirect');",
            "Route::get('/callback', [CodeController::class, 'callback'])->name('callback');\n",
        ];

        $readFile = fopen($this->routes, 'r');

        $index = 0;
        if ($readFile) {
            while (!feof($readFile)) {
                $line = fgets($readFile);
                if (strpos($line, '::get(')) {
                    foreach ($routes as $value) {
                        $this->addString($this->routes, $index, "$value\n");
                        $index += 1;
                    }
                }
                $index += 1;
            }
        }

    }
}
