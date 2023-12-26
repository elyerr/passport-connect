# Passport Connect (Testing)
Permite conectar fácilmente cualquier micro-servicio al servidor principal, este plugin funciona solo con el servidor de autorización [outh2-passport-server](https://gitlab.com/elyerr/outh2-passport-server)

### Dependencias
[Redis](https://redis.io/), Redis es un motor de base de datos en memoria, necesario para gestionar las sesiones con el servidor de autorización, independientemente del sistema de sesión que esté usando tu micro servicio, deberás instalarlo en tu servidor al ser de codigo abierto no implicara pagos adicionales por su uso. 

## CONFIGURACIÓN

### PUBLICAR CONFIGURACIÓN
```
php artisan vendor:publish --tag=passport_connect
```

### CREAR CLIENTE PÚBLICO
- este se debe hacer a través de la terminal dentro del servidor de aouth2 ejecutando el siguiente comando
```
php artisan passport:client --public
```
El comando realizará tres preguntas antes de generar un id del cliente
- en la primera pegunta puedes ingresar un número cualquiera que no debe repetirse con otros clientes ya existentes. Por ejemplo 100
- en la segunda pregunta deberás asignar un nombre al cliente puedes agregar el nombre de tu app para que tenga relación, por ejemplo "users"
- en el tercer argumento deberás agregar un el dominio de su micro servicio agregando la ruta **callback** por ejemplo **http://users.dominio.dom/callback**, al final de esto te entregará un valor uuid parecido a este `9af032fb-e984-4d0a-a6b8-bebabf129c14` que deberás asignar en la configuración de passport-connect en la llave **server_id**

#### DESHABILITAR ENCRIPTADO PARA LAS COOKIES 
en laravel deberás agregar el constructor dentro del middleware **EncryptCookies** e importar la siguiente denominación, con esto evitarás que encripte las cookies que genera el paquete
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

### GENERAR RUTAS DE CREDENCIALES
Recuerda que antes de poder usar las rutas deberás configurar las rutas todo lo necesario en el archivo **passport-connect.php** en la carpeta **config** de laravel
```
php artisan passport-connect:client-public
```

## MIDDLEWARES
Clases encargadas de interceptar las peticiones y actuar con base en las credenciales del usuario, son las encargadas de crear el puente con el sistema de autorización para proporcionarte acceso al sistema.

- **Authorization**: se encarga de verificar si el token jwt es correcto y también se encarga de generar nuevas credenciales cuando estas vencen, siempre que los datos sean válidos. Este middleware debe usarse para proteger todas las rutas del micro servicio que requiera una authorizacion minima, esta debe ser usada sola o en conjunto con los demás middleware.
- **CheckClientCredentials**: el funcionamiento de este middlware comprueba si un cliente pertenece al gran_type client_credentials.
- **CheckForAnyScope**: Encargado de verificar si los scopes o permisos asignados al usuario corresponden a la zona donde solicita acceso, a diferencia del siguiente bastara con que solo tenga un permisos de todos los que se requieren para poder acceder.
- **CheckScopes**: funciona igual al anterior pero este verificara que todos los scopes estén precentes para poder dejar pasar la petición.

El middleware 2,3 y 4 deben ser usados con el primer middleware para su correcto funcionamiento, si bien el primero no requiere de los demás  necesariamente requieren del primero.