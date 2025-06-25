<?php

namespace Elyerr\Passport\Connect\Http;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;
use Elyerr\Passport\Connect\Http\Request;
use Elyerr\Passport\Connect\Traits\Config;
use GuzzleHttp\Exception\RequestException;

class Client
{
    use Config;

    /**
     * Guzzle HTTP Client
     * @var HttpClient
     */
    protected $client;

    /**
     * Default headers
     * @var array
     */
    protected $headers = [
        'Accept' => 'application/json',
        'Connection' => 'keep-alive',
    ];

    /**
     * Constructor
     * 
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        //Add authorization header
        $this->headers['Authorization'] = $this->loadCredentials();

        $this->client = new HttpClient([
            'base_uri' => rtrim($this->env()->server, '/') . '/',
            'timeout' => 10,
            'http_errors' => false,
            'headers' => array_merge($this->headers, $headers),
            'keep_alive' => true,
            'verify' => $this->env()->environment == 'production' ? true : false,
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 60,
                CURLOPT_TCP_KEEPINTVL => 20,
            ]
        ]);
    }

    /**
     * Add new header to the request
     * @param array $headers
     * @return void
     */
    public function addHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
        $this->updateHeaders();
    }

    /**
     * Update headers in client
     * @return void
     */
    protected function updateHeaders(): void
    {
        $this->client = new HttpClient([
            'base_uri' => $this->client->getConfig('base_uri'),
            'timeout' => 10,
            'http_errors' => false,
            'headers' => $this->headers,
            'keep_alive' => true,
        ]);
    }

    /**
     * Perform a GET request
     * @param string $uri
     * @param array $query
     * @return \stdClass
     */
    public function get(string $uri, array $query = []): \stdClass
    {
        return $this->request('GET', $uri, ['query' => $query]);
    }

    /**
     * Perform a POST request
     * @param string $uri
     * @param array $data
     * @return \stdClass
     */
    public function post(string $uri, array $data = []): \stdClass
    {
        return $this->request('POST', $uri, ['form_params' => $data]);
    }

    /**
     * Perform a PUT request
     * @param string $uri
     * @param array $data
     * @return \stdClass
     */
    public function put(string $uri, array $data = []): \stdClass
    {
        return $this->request('PUT', $uri, ['form_params' => $data]);
    }

    /**
     * Perform a DELETE request
     * @param string $uri
     * @param array $data
     * @return \stdClass
     */
    public function delete(string $uri, array $data = []): \stdClass
    {
        return $this->request('DELETE', $uri, ['form_params' => $data]);
    }

    /**
     * Core request method
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return \stdClass
     */
    protected function request(string $method, string $uri, array $options = []): \stdClass
    {
        try {
            $response = $this->client->request($method, $uri, $options);

            return $this->formatResponse($response);
        } catch (RequestException $e) {
            return (Object) [
                'error' => true,
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];
        }
    }

    /**
     * Format response to array
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return object
     */
    protected function formatResponse(ResponseInterface $response): \stdClass
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        return (object) [
            'status' => $response->getStatusCode(),
            'data' => (object) $data ?? $body,
        ];
    }
    /**
     * Retrieve the token jwt to authorize user
     * @throws \Exception
     * @return bool|float|int|string|null
     */
    private function loadCredentials()
    {
        $request = new Request();

        $token = $request->header('Authorization');

        if (!empty($token)) {
            $token = $request->cookie($this->env()->jwt_token);
        }

        if (!str_starts_with($token, 'Bearer ')) {
            $token = "Bearer $token";
        }

        return $token;
    }
}
