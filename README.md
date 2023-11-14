## Passport Connect (Testing)
Permite connectar facilmente cualquier culquier microservicio al servidor principal, este plugin funciona solo con el servidor de authorizacion [outh2-passport-server](https://gitlab.com/elyerr/outh2-passport-server)

### Dependencias
- [Redis](https://redis.io/),  Redis es un motor de base de datos en memoria, necesario para gestionar las sessiones con el servidor de authorizacion, independientemente del sistema de session que este usando tu microservicio, deberas instalarlo en tu servidor al ser de codigo abierto no implicara pagos adicionales por su uso. 

## Advertencias
- Si estas usando laravel o cualquier otro framework que encripte las cookies de forma automatica, deveras evitar que vuelva a encriptar las cookies, para eso tienes dos opciones
  - desabilitar la funcion en el framework de encriptado automatico, ya que las sesiones las administrara este plugin es a travez de redis por lo que es es inecesario ya que el paquete encripta las cookies que usa en la session de forma automaticatica.
  - la otra opcion que tendras, es agregar las cookies a la excepcion para que no las encripte para eso deberas hacer uso del trait `config` e importar la denominacion  `Elyerr\Passport\Connect\Traits\Config` dentro de la clase que se encargue del encriptado y deberas agregar estas llaves a la excepcion 
    ```
        /**
         * devuelve los nombre de las cookies que generara para la session
         * 
         * @return Array
         */
        public function cookies()
            {
                return [
                    $this->env()->ids->server_id,
                    $this->env()->ids->server_key,
                    $this->env()->ids->jwt_token,
                    $this->env()->ids->jwt_refresh,
                    $this->env()->ids->csrf_refresh
                    ];
            }
    ```

## Configuracion
Antes luego de instalar el paquete deberas configurar las llaves necesarias en el archivo config del plugin, puedes ajustar los valores dependiendo de la configuracion de tu servidor.

#### Rutas
  - `CodeController` : controlador que administra la gestion de un usuario publico, deberas generarle una uri en tu proyecto para su correcto funcionamiento.
  - Esquema
    - `/login` : muestra una vista para generar la sesion
    - `/redirect` : Redirecciona al servidor de autorizacion para solicitar permiso
    - `/callback` : Ruta que generara las credenciales necesarias para la coneccion con el sever.

#### Middleware
clases encargadas de interceptar las petciones y actuar en base a las credenciales del usaurio, estas, son las encargadas de crear el puente con el sistema de autorizacion para porporcionarte acceso al sistema.

- **Authorization**: se encarga de verificar si el token jwt es correcto y tambien se encarga de generar nuevas credenciales cuando estas vencen, simpre que los datos sen validos. este middlware debe usarse para porteger todas las rutas del microservicion que requiera una authorizacion minima, esta debe ser usada sola o en conjunto con los demas middleware.
- **CheckClientCredentials**: el funcionamiento de este middlware comprueba si un cliente pertenece al gran_type client_credentials.
- **CheckForAnyScope**: Encargado de verificar si los scopes o permisos asignados al usario corresponden a la zona donde solicita acceso, a diferencia del siguiente bastara con que solo tenga un permisos de todos los que  se requiren para poder acceder.
- **CheckScopes**: funciona igual al anterior pero este verificara que todos los scopes esten precentes para poder dejar pasar la peticion.

El middleware 2,3 y 4 deben ser usaudos con el primer middleware para su correcto funcionamiento, si bien el primero no no requiere de los demas los demas necesarimente requieren del primero.