<?php

namespace Elyerr\Passport\Connect\Console;

use Elyerr\Passport\Connect\Traits\Environment;
use Illuminate\Console\Command;

class ClientConsole extends Command
{
    use Environment;

    /**
     * @var string
     */
    protected $routes;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "passport-connect:install";

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = "Set the config for public client";

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
        $this->addEnvironmentKey();
        $this->addErrorView();
        $this->addAuthView();
        $this->addMiddleware();
        $this->uploadImport();
        $this->uploadRoutes();

        $this->info("The config is ready");
    }

    /**
     * Add new environment into the .env file
     * @return void
     */
    public function addEnvironmentKey()
    {
        $file = base_path('.env');

        $this->addString($file, 8, "SERVER_ID=\n");
    }

    /**
     * Load CodeController
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
     * Register the routes 
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

    /**
     * Add string
     * @param mixed $file
     * @param mixed $index
     * @param mixed $value
     * @param mixed $replace
     * @param mixed $repeat
     * @return void
     */
    public function addString($file, $index, $value, $replace = 0, $repeat = false)
    {
        $lines = $this->fileToArray($file);

        if (!$repeat and strpos(file_get_contents($file), $value) === false) {

            array_splice($lines, $index, $replace, $value);

        } elseif ($repeat) {
            array_splice($lines, $index, $replace, $value);
        }
        file_put_contents($file, $lines);
    }

    /**
     * Transform any file in array collection
     * @param mixed $file
     * @return array
     */
    public function fileToArray($file)
    {
        $readFile = fopen($file, 'r');

        $lines = [];

        if ($readFile) {
            while (!feof($readFile)) {
                $line = fgets($readFile);
                array_push($lines, $line);
            }
            fclose($readFile);
        }

        return $lines;
    }
}
