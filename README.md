# Passport Connect
Make a easy the config and connect with oauth2 server [oauth2-server](https://gitlab.com/elyerr/oauth2-passport-server) 

## Configuration

### Config file
```bash
php artisan vendor:publish --tag=passport_connect
```

### Generate credentials
The credentials can be generate in the oauth2 server in the section developers at the menu clients

## Environment Keys (Example)

```bash
SERVER_ID="9b3a1165-5af7-4619-a04d-a51d16134acf"

#Domain OAuth2 Server
SERVER=https://server.domain.com

#Prompt mode (consent|none|login)
PROMPT_MODE=none

#Domain to generate the cookie for credentials
SESSION_DOMAIN=server.domain.com

#Name of the cookies token and refresh token
PASSPORT_TOKEN="oauth_server"
PASSPORT_REFRESH="${PASSPORT_TOKEN}_refresh"


#Add scopes separates by spaces or * for all scope available to the user
CLIENT_SCOPES='scope1 scope2 scopex'

#Page to redirect after the login
REDIRECT_TO='/'
```

## Middleware 

- **Authorization**:  To check only credentials 
- **CheckClientCredentials**:  To check client credentials
- **CheckForAnyScope**: To Check Credentials and any scopes
- **CheckScopes**:  To check credentials and check all scopes 


# Extra functions can use
Trait Available
```sh
use Elyerr\Passport\Connect\Traits\Passport;
```
To Check scope for the current user
```sh
$this->userCan('assets');
```

Get the current user
```sh
$this->user()
``` 

 