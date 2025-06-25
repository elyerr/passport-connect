<?php

namespace Elyerr\Passport\Connect\Http;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request
{
    /**
     * Symfony request instance
     *
     * @var HttpRequest
     */
    protected HttpRequest $request;

    /**
     * Build request wrapper from current request
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     */
    public function __construct(HttpRequest $request = null)
    {
        $this->request = $request ?: HttpRequest::createFromGlobals();
    }

    /**
     * Get a query or post input by key
     * @param string $key
     * @param mixed $default
     */
    public function input(string $key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    /**
     * Get all input (GET, POST merged)
     * @return array
     */
    public function all(): array
    {
        return $this->request->request->all() + $this->request->query->all();
    }

    /**
     * Get query string parameter
     * @param string $key
     * @param mixed $default
     * @return bool|float|int|string|null
     */
    public function query(string $key, $default = null)
    {
        return $this->request->query->get($key, $default);
    }

    /**
     * Get POST parameter
     * @param string $key
     * @param mixed $default
     * @return bool|float|int|string|null
     */
    public function post(string $key, $default = null)
    {
        return $this->request->request->get($key, $default);
    }

    /**
     * Get a header value
     * @param string $key
     * @param mixed $default
     * @return string|null
     */
    public function header(string $key, $default = null)
    {
        return $this->request->headers->get($key, $default);
    }

    /**
     * Get all headers
     * @return array<array<string|null>|string|null>
     */
    public function headers(): array
    {
        return $this->request->headers->all();
    }

    /**
     * Get the request method
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get full URI
     * @return string
     */
    public function fullUrl(): string
    {
        return $this->request->getUri();
    }

    /**
     * Check if request is POST
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
    }

    /**
     * Check if request is GET
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->request->isMethod('GET');
    }

    /**
     * Access to the original Symfony Request if needed
     * @return HttpRequest
     */
    public function symfony(): HttpRequest
    {
        return $this->request;
    }

    /**
     * Retrieve the cookie
     * @param string $key
     * @param mixed $default
     * @return bool|float|int|string|null
     */
    public function cookie(string $key, $default = null)
    {
        return $this->request->cookies->get($key, $default);
    }
}
