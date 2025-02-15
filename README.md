# Elyerr Passport Connect

**Elyerr Passport Connect** is a lightweight PHP library designed to **simplify the connection of microservices and third-party modules** through an **OAuth2 Passport server**. This library provides seamless integration with [OAuth2 Passport Server](git@gitlab.com:elyerr/oauth2-passport-server.git), allowing applications to authenticate and authorize users efficiently.

---

## Key Features

- **Microservices Authentication:** Easily connect various services to a central OAuth2 authorization server.
- **OAuth2 Compliance:** Uses `guzzlehttp/guzzle` for handling HTTP requests and integration with the OAuth2 Passport Server.
- **Automatic Service Provider Registration:** Automatically registers a service provider for seamless integration.

---

## Installation

To include Elyerr Passport Connect in your project, run the following command using Composer:

```bash
composer require elyerr/passport-connect
```

## Requirements

- PHP 7.4 or higher
- Laravel Framework (version 8.x or higher)
- Guzzle 7.8 or higher

## Publish configuration file

After installing the package, you need to publish the configuration file to customize its settings according to your needs.

- Run the following command to publish the configuration file:

```bash
php artisan vendor:publish --tag=passport_connect
```

## Install Dependencies

To ensure all necessary dependencies are installed and configured properly, run the following command:

```bash
php artisan passport-connect:install
```

## Middleware Configuration

The package provides four types of middleware that can be used to secure routes with different levels of authorization. Here is a breakdown of each middleware and its purpose:

- server
  `Class: \Elyerr\Passport\Connect\Middleware\Authorization::class`
  This middleware is used for basic authentication, ensuring that the request is authenticated using the OAuth2 Passport Server.
  Example usage:

  ```bash
  Route::get('/admin', [AdminController::class, 'index'])->middleware('server');
  ```

- scope
  `Class: \Elyerr\Passport\Connect\Middleware\CheckForAnyScope::class`
  Ensures that at least one of the specified scopes is present in the authenticated user's token. This is used to a authorize users based on their roles or permissions.
  Example usage:

  ```bash
  Route::get('/admin', [AdminController::class, 'index'])->middleware('scope:admin,user');
  ```

- scopes
  `Class: \Elyerr\Passport\Connect\Middleware\CheckScopes::class`
  Ensures that all specified scopes are present in the user's token. This is useful for enforcing stricter access control.
  Example usage:

  ```bash
  Route::get('/admin', [AdminController::class, 'index'])->middleware('scopes:admin,user');
  ```

- client
  `Class: \Elyerr\Passport\Connect\Middleware\CheckClientCredentials::class`
  Allows machine-to-machine (server-to-server) connections without user intervention. This is typically used for automated processes or backend services.
  Example usage:
  ```bash
  Route::get('/admin', [AdminController::class, 'index'])->middleware('client:admin,user');
  ```

### Adding Middleware to the Kernel

- For Laravel versions below 11: The middleware is automatically added to the app/Http/Kernel.php file during installation.
- For Laravel 11 and above: You will need to add the middleware manually to the $routeMiddleware array in app/Http/Kernel.php as shown below:

```bash
protected $routeMiddleware = [
    'server' => \Elyerr\Passport\Connect\Middleware\Authorization::class,
    'scope' => \Elyerr\Passport\Connect\Middleware\CheckForAnyScope::class,
    'scopes' => \Elyerr\Passport\Connect\Middleware\CheckScopes::class,
    'client' => \Elyerr\Passport\Connect\Middleware\CheckClientCredentials::class,
];
```

Each middleware can be used like any other Laravel middleware by passing parameters as comma-separated values.

## Required Configuration Variables

The following environment variables are essential for configuring the application’s behavior as either an internal module or a third-party application when connected to the OAuth2 Passport Server:

- APP_URL (Host)
  Defines the host of the application. This should match the base URL of your app, typically defined in the .env file.
  Example:

```bash
APP_URL=https://example.com
```

- PASSPORT_MODULE (Module Behavior)
  Determines whether the application behaves as an internal module (when set to true) or as a third-party app (false).
  Example:

```bash
PASSPORT_MODULE=true
```

- PASSPORT_SERVER (OAuth2 Server URL)
  Specifies the URL of the OAuth2 Passport Server that will handle authentication and authorization.
  Example:

```bash
PASSPORT_SERVER=https://auth.example.com
```

- PASSPORT_MODULE_COOKIES_NAMES (Server Cookie Names)
  A comma-separated list of cookie names required for authentication if the app is on the same domain as the OAuth2 Passport Server. Typically, this includes the passport cookie (used for session management) and the csrf cookie (for cross-site request forgery protection). When these cookies are present, the app behaves as an internal module rather than a third-party application.
  Example:

```bash
PASSPORT_MODULE_COOKIES_NAMES="passport_cookie_name,cookie_csrf"
```

- PASSPORT_REDIRECT_TO (Redirect After Login)
  Defines the page to which the user will be redirected after a successful login. This allows you to control the user’s post-login experience.
  Example:

```bash
PASSPORT_REDIRECT_TO=/dashboard
```

- PASSPORT_LOGIN_TO (Login Route)
  Specifies the route where users should be directed for login. This route will typically point to your app’s login page.
  Example:

```bash
    PASSPORT_LOGIN_TO=/login
```

### Third-Party Applications

- These settings are specific to third-party applications that connect to the OAuth2 Passport Server from a different domain. They control client credentials, authentication prompts, token handling, and cookie settings for secure communication:

- Client Credentials

  PASSPORT_SERVER_ID (Client ID)
  The Client ID generated on the OAuth2 Passport Server, which is essential for authenticating third-party applications.
  Example:

  ```bash
  PASSPORT_SERVER_ID=your-client-id
  ```

- Authorization Prompt Mode

  PASSPORT_PROMPT_MODE (Prompt Behavior)
  Defines how the OAuth2 server will prompt the user during authorization. Acceptable values:
  **none**: No user prompt.
  **consent**: Prompts the user for consent.
  **login**: Forces the user to log in again.
  Example:

```bash
PASSPORT_PROMPT_MODE=consent
```

- Scopes

  PASSPORT_CLIENT_SCOPES (Authorization Scopes)
  A list of authorization scopes that the client requests from the user. You can specify multiple scopes or use \* to request all available scopes.
  Example:

```bash
PASSPORT_CLIENT_SCOPES=read,write,admin
```

- Token Storage

  PASSPORT_TOKEN (JWT Token Cookie Name)
  The name of the cookie that stores the JWT access token. It defaults to a slugified version of the app name (e.g., passport_oauth_server).
  Example:

```bash
PASSPORT_TOKEN=your_app_jwt_token
```

PASSPORT_REFRESH (JWT Refresh Token Cookie Name)
The name of the cookie that stores the JWT refresh token.
Example:

```bash
PASSPORT_REFRESH=your_app_refresh_token
```

- Cookie Configuration

  PASSPORT_DOMAIN_SERVER (Cookie Domain)
  Specifies the domain for the cookies.
  Example:

  ```bash
  PASSPORT_DOMAIN_SERVER=.example.com
  ```

  PASSPORT_TIME_EXPIRES (Expiration Time)
  Sets the expiration time for the cookie, typically in seconds.
  Example:

```bash
PASSPORT_TIME_EXPIRES=3600
```

PASSPORT_SECURE_COOKIE (Secure Cookie)
Enforces the use of https for cookies when set to true.
Example:

```bash
PASSPORT_SECURE_COOKIE=true
```

PASSPORT_HTTP_ONLY_COOKIE (HTTP-Only Cookie)
Prevents client-side scripts from accessing cookies, enhancing security.
Example:

```bash
PASSPORT_HTTP_ONLY_COOKIE=true
```

PASSPORT_SAME_SITE_COOKIE (Same-Site Policy)
Controls cross-site cookie behavior. Acceptable values:

    **lax**: Allows cookies with top-level navigation.
    **strict**: Prevents all cross-site cookies.
    **none**: No restrictions (only use with secure cookies).
    Example:

```bash
PASSPORT_SAME_SITE_COOKIE=lax
```

PASSPORT_PARTITIONED_COOKIE (Partitioned Cookies)
Enables partitioned cookies, which are accessible only in specific contexts.
Example:

```bash
PASSPORT_PARTITIONED_COOKIE=false
```

These variables ensure secure and flexible integration with the OAuth2 Passport Server when the application is deployed on a different domain.

## Example Configuration for Module Mode

The following environment variables configure the application to behave as an internal module when integrated with the OAuth2 Passport Server. **This configuration is only applicable when the application is on the same domain as the OAuth2 Passport Server**:

```bash
# OAuth2 Server URL
PASSPORT_SERVER=https://auth.elyerr.xyz

# Required cookies for authentication
PASSPORT_MODULE_COOKIES_NAMES="oauth2_server,oauth2_server_csrf"

# Redirect page after login (defaults to the root if empty)
PASSPORT_REDIRECT_TO=""

# Login route used by the module
PASSPORT_LOGIN_TO="login"

# Determines whether the application behaves as a module (false for third-party apps)
PASSPORT_MODULE=true
```

## Example Configuration for Third-Party Applications

The following environment variables configure the application to behave as a third-party application when connecting to the OAuth2 Passport Server:

```bash
# OAuth2 Server URL
PASSPORT_SERVER=https://auth.elyerr.xyz

# Redirect page after login
PASSPORT_REDIRECT_TO=""

# Login route
PASSPORT_LOGIN_TO="login"

# Determines the app mode (false indicates a third-party app)
PASSPORT_MODULE=false

# Third-party app credentials generated on the OAuth2 Passport Server
PASSPORT_SERVER_ID="9e310cc9-5e78-4ab4-922f-cfac3c00635d"

# Authorization prompt mode
# Options:
# - none: No prompt to the user
# - consent: Prompt for consent
# - login: Forces a new login
PASSPORT_PROMPT_MODE=consent

# OAuth2 Passport Server domain for cookie settings
PASSPORT_DOMAIN_SERVER="test.elyerr.xyz"

# JWT token and refresh token cookie names
PASSPORT_TOKEN="app_token"
PASSPORT_REFRESH="${PASSPORT_TOKEN}_refresh"

# Secure cookie configurations
PASSPORT_SECURE_COOKIE=true  # Ensures cookies are transmitted over HTTPS only
PASSPORT_HTTP_ONLY_COOKIE=true  # Prevents cookies from being accessed via JavaScript
PASSPORT_PARTITIONED_COOKIE=false  # Disables O enable partitioned cookie settings
```

## Extra functions

To enhance the application's functionality, you can use the `Elyerr\Passport\Connect\Traits\Passport` trait, which provides the following methods:

- **userCan($scope)**:
  Checks if the authenticated user has access to the specified scope. Returns true if the user has the required scope.

- **user()**:
  Returns the authenticated user's information, including any relevant details for authorization.

- **logout()**:
  Logs out the authenticated user, terminating their session.

These methods make it easier to manage user permissions, retrieve user details, and handle session management efficiently.

## Web Routes for OAuth2 Passport Integration

Here are the three essential web routes for OAuth2 Passport integration from controller
`Elyerr\Passport\Connect\Controllers\CodeController`, These routes handle the authentication flow:

- Login View:
  Displays the login page where the user can enter their credentials.

```bash
Route::get('/login', [CodeController::class, 'login'])->name('login');
```

- Redirect:
  Transfers the necessary credentials to the authorization server to initiate the authentication process.

```bash
Route::get('/redirect', [CodeController::class, 'redirect'])->name('redirect');
```

- Callback:
  Handles the authorization server's response and generates the authentication credentials (such as access tokens) required to access protected resources.

```bash
Route::get('/callback', [CodeController::class, 'callback'])->name('callback');
```
