# Passport Connect (Testing)
Permite connectar facilmente cualquier culquier microservicio al servidor principal, este plugin funciona solo con el servidor de authorizacion [outh2-passport-server](https://gitlab.com/elyerr/outh2-passport-server)

### Dependencias
[Redis](https://redis.io/),  Redis es un motor de base de datos en memoria, necesario para gestionar las sessiones con el servidor de authorizacion, independientemente del sistema de session que este usando tu microservicio, deberas instalarlo en tu servidor al ser de codigo abierto no implicara pagos adicionales por su uso. 

## CONFIGURACION

### PUBLICAR CONFIGURACION
```
php artisan vendor:publish --tag=passport_connect
```

### CREAR CLIENTE PUBLICO
- este se debe hacer a traves de la terminar dentro del servidor de aouth2 ejecutando el siguiente comando
```
php artisan passport:client --public
```
El comando realizara tres preguntas antes de generar un id del cliente
- en la primera pegrunta puedes ingresar un numero cualquiera que no debe repetirse con otros clientes ya existentes. por ejemplo 100
- en la segunda pregunta deberas asignar un nombre al cliente puedes agregar el nombre de tu app para que tenga relacion por ejemplo "users"
- en el tercer argumento deberas agregar un el dominion de su microservicio agregando la ruta **callback** por ejemplo **http://users.dominio.dom/callback**, al final de esto te entgregará un valor uuid parecido a este `9af032fb-e984-4d0a-a6b8-bebabf129c14` que deberas asignar en la configuracion de passport-connect en la llave **server_id**


#### DESHABILITAR ENCRYPTADO PARA LAS COOKIES 
en laravel deberas agregar el contructor dentro del middleware **EncryptCookies** e importar la siguiente denominación, con esto evitaras que encripte las cookies que genera el paquete
  
```
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
```

- Constructor de la clase
```
public function __construct(EncrypterContract $encrypter)
    {   
        parent::__construct($encrypter);
        
        $except = [
            config('passport_connect.ids.server_id'),
            config('passport_connect.ids.server_key'),
            config('passport_connect.ids.jwt_token'),
            config('passport_connect.ids.jwt_refresh'),
        ];

        $this->except = array_merge($this->except, $except);
    }
```

### GENEREAR RUTAS DE CREDENCIALES
Recuerda que antes de poder usar las rutas deberas configurar las rutast todo lo necesario en el archivo **passport-connect.php** en la carpeta **config** de laravel
```
php artisan passport-connect:client-public
```

## MIDDLWARES
clases encargadas de interceptar las petciones y actuar en base a las credenciales del usaurio, son las encargadas de crear el puente con el sistema de autorizacion para porporcionarte acceso al sistema.

- **Authorization**: se encarga de verificar si el token jwt es correcto y tambien se encarga de generar nuevas credenciales cuando estas vencen, simpre que los datos sen validos. este middlware debe usarse para porteger todas las rutas del microservicion que requiera una authorizacion minima, esta debe ser usada sola o en conjunto con los demas middleware.
- **CheckClientCredentials**: el funcionamiento de este middlware comprueba si un cliente pertenece al gran_type client_credentials.
- **CheckForAnyScope**: Encargado de verificar si los scopes o permisos asignados al usario corresponden a la zona donde solicita acceso, a diferencia del siguiente bastara con que solo tenga un permisos de todos los que  se requiren para poder acceder.
- **CheckScopes**: funciona igual al anterior pero este verificara que todos los scopes esten precentes para poder dejar pasar la peticion.

El middleware 2,3 y 4 deben ser usaudos con el primer middleware para su correcto funcionamiento, si bien el primero no no requiere de los demas los demas necesarimente requieren del primero.