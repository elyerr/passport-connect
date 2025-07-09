# Passport Connect SDK

**Passport Connect** is a lightweight and framework-agnostic PHP SDK designed to **simplify the integration of OAuth2 authentication** across microservices using a central **[Passport OAuth2 server](https://gitlab.com/elyerr/oauth2-passport-server)**.

This library provides a clean and minimal interface to authenticate users or services via an external Passport server. It supports seamless use in Laravel, Symfony, or plain PHP projects, enabling secure authorization and authentication workflows with minimal setup.

---

## Installation & Configuration

Once installed, you can publish the configuration file by running:

```bash
vendor/bin/passport_connect
```

### Example `.env` Configuration

```env
PASSPORT_SERVER=https://auth.elyerr.xyz               # OAuth2 Passport server URL
PASSPORT_CLIENT_ID=9f3a03df-df61-4d65-a850-5b53240f1b4a  # OAuth2 client ID
PASSPORT_REDIRECT_TO=/user/account                    # Redirect route after successful login
PASSPORT_LOGIN_TO=login                               # Local route for unauthenticated users
PASSPORT_PROMPT_MODE=none                             # Prompt modes: none | consent | login | internal
PASSPORT_DOMAIN_SERVER=app.domain.com                 # Cookie domain
PASSPORT_TOKEN_NAME=passport_server                   # Name of the auth cookie
PASSPORT_SECURE_COOKIE=true                           # Only allow cookie over HTTPS
PASSPORT_HTTP_ONLY_COOKIE=true                        # Disallow JavaScript access to cookie
PASSPORT_PARTITIONED_COOKIE=true                      # Enables cross-domain cookie isolation (for CHIPS compatibility)
```

---
## Middleware

Passport Connect includes a set of middleware to easily protect routes and validate access based on tokens and scopes.

### `Authorization`

Performs basic user authentication using the access token provided.

```php
\Elyerr\Passport\Connect\Middleware\Authorization::class
```

---

### `CheckForAnyScope`

Validates that the user has **at least one** of the required scopes.

```php
\Elyerr\Passport\Connect\Middleware\CheckForAnyScope::class
```

---

### `CheckScopes`

Validates that the user has **all** of the specified scopes.

```php
\Elyerr\Passport\Connect\Middleware\CheckScopes::class
```

---

### `CheckClientCredentials` _(Coming Soon)_

Intended to validate **client credentials** authentication (not yet implemented).

```php
\Elyerr\Passport\Connect\Middleware\CheckClientCredentials::class
```
---
## Cookie Encryption in Laravel

To allow the SDK to access the **authorization token stored in cookies**, you need to add the cookie name to the `$except` list of Laravel's `EncryptCookies` middleware. This prevents Laravel from encrypting the cookie and allows the token to be read properly.

---

### Laravel 10 and Below

In Laravel versions 10 or below, you can directly edit the `EncryptCookies` middleware:

```php
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies extends Middleware
{
    protected $except = [];

    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        $this->except = array_merge($this->except, [
            config('passport_connect.jwt_token'),
        ]);
    }
}
```

---

### Laravel 11 and Above

Starting from Laravel 11, the `EncryptCookies` middleware is no longer exposed by default. You need to:

1. **Create a new middleware** that extends `\Illuminate\Cookie\Middleware\EncryptCookies`.
2. **Replace the default middleware** in `bootstrap/app.php`.

#### 1⃣ Create a Custom `EncryptCookies` Middleware

```php
namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncryptCookies;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies extends BaseEncryptCookies
{
    protected $except = [];

    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        $this->except = array_merge($this->except, [
            config('passport_connect.jwt_token'),
        ]);
    }
}
```

#### 2. Register and Replace Middleware in `bootstrap/app.php`

In Laravel 11+, you must explicitly register and configure middleware using the fluent middleware API:

```php
use Elyerr\Passport\Connect\Middleware\CheckScopes;
use Elyerr\Passport\Connect\Middleware\Authorization;
use Laravel\Passport\Http\Middleware\CheckCredentials;
use Elyerr\Passport\Connect\Middleware\CheckForAnyScope;
use App\Http\Middleware\EncryptCookies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register Passport Connect middleware aliases
        $middleware->alias([
            'scope' => CheckForAnyScope::class,
            'scopes' => CheckScopes::class,
            'server' => Authorization::class,
            'client-credentials' => CheckCredentials::class,
        ]);

        // Replace Laravel's default EncryptCookies middleware
        $middleware->web(
            remove: [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
            ],
            append: [
                EncryptCookies::class,
            ]
        );
    });
```

---

With this setup, your application will properly expose the cookie that contains the token and ensure Passport Connect SDK can access it seamlessly.

---

## Service: `Elyerr\Passport\Connect\Services\Passport`

This service provides access to core Passport server operations, such as getting the current user, validating scopes, and logging out. The token is automatically injected into the service

### `userCan(string $scope): void`

Checks if the current user is authorized for a specific scope.

- **Parameter:** `string $scope`
- **Throws:** `Exception` if user is not authorized

### `user(): \stdClass`

Retrieves the currently authenticated user.

- **Returns:** An object containing the user data
- **Throws:** `Exception` if authentication fails

### `logout(Request $request): \stdClass`

Logs out the authenticated user.

- **Parameter:** `Request $request`
- **Returns:** An object with the logout response
- **Throws:** `Exception` on failure or unauthorized access

### `access(): \stdClass`

Fetches all scopes assigned to the current user.

- **Returns:** An object containing the scopes
- **Throws:** `Exception` on failure

---

---

## ⚙️ Features

- OAuth2 login via external Passport server
- Scopes and permission checks
- Token-based authentication using secure cookies
- Compatible with Laravel, Symfony, and native PHP
- No framework dependency

---

## Api Documentation

Full documentation and usage examples can be found in the source code and will be available soon. Meanwhile, you can check these resources:

- [API](https://documenter.getpostman.com/view/5625104/2sB2xBDq6o)
- [Oauth2 passport server](https://gitlab.com/elyerr/oauth2-passport-server/-/wikis/home)

---

## 🛠️ License

Released under the GPL-3.0 License. See [LICENSE](./LICENSE) for details.
