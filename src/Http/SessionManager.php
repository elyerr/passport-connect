<?php

namespace Elyerr\Passport\Connect\Http;

use Elyerr\Passport\Connect\Traits\Config;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage; 
class SessionManager
{
    use Config;

    /**
     * The Symfony session instance.
     *
     * @var Session
     */
    protected $session;

    /**
     * Create and start a new session using custom configuration.
     */
    public function __construct()
    {
        $storage = new NativeSessionStorage([
            'name' => $this->env()->jwt_token,
            'cookie_domain' => $this->env()->cookie->domain,
            'cookie_path' => $this->env()->cookie->path,
            'cookie_secure' => $this->env()->cookie->secure,
            'cookie_httponly' => $this->env()->cookie->http_only,
            'cookie_samesite' => $this->env()->cookie->same_site,
        ]);

        $this->session = new Session($storage);

        try {
            $this->session->start();
        } catch (\Throwable $th) {
        }
    }

    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->session->get($key, $default);
    }

    /**
     * Store a value in the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * Check if a key exists in the session.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->session->has($key);
    }

    /**
     * Remove a key from the session.
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        $this->session->remove($key);
    }

    /**
     * Get all session data.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->session->all();
    }

    /**
     * Get the raw Symfony session instance.
     *
     * @return Session
     */
    public function raw(): Session
    {
        return $this->session;
    }

    /**
     * Get flash gat
     * @return \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
     */
    public function getFlashBag()
    {
        return $this->session->getFlashBag();
    }


    /**
     * Get session id
     * @return string
     */
    public function getId()
    {
        return $this->session->getId();
    }

    /**
     * Get session name
     */
    public function getName(): string
    {
        return $this->env()->jwt_token;
    }
}
