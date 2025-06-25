<?php

namespace Elyerr\Passport\Connect\Support;

use Symfony\Component\HttpFoundation\Response as ResponseHttp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Cookie;

class Response
{
    /**
     * JSON response
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     */
    public static function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse(
            $data,
            $status,
            $headers,
            $options
        );
    }

    /**
     * Plain text response
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return ResponseHttp
     */
    public static function text(string $content, int $status = 200, array $headers = []): ResponseHttp
    {
        return new ResponseHttp(
            $content,
            $status,
            array_merge([
                'Content-Type' => 'text/plain; charset=UTF-8',
            ], $headers)
        );
    }

    /**
     * HTML response
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return ResponseHttp
     */
    public static function html(string $content, int $status = 200, array $headers = []): ResponseHttp
    {
        return new ResponseHttp($content, $status, array_merge([
            'Content-Type' => 'text/html; charset=UTF-8',
        ], $headers));
    }

    /**
     * Attach a cookie to the response
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     * @return ResponseHttp
     */
    public static function withCookie(ResponseHttp $response, Cookie $cookie): ResponseHttp
    {
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * Redirect to a given URL
     * @param string $url
     * @param int $status
     * @return ResponseHttp
     */
    public static function redirect(string $url, int $status = 302): ResponseHttp
    {
        return new ResponseHttp('', $status, [
            'Location' => $url,
        ]);
    }

    /**
     * Download a file
     * @param string $filePath
     * @param string $filename
     * @param array $headers
     * @return StreamedResponse
     */
    public static function download(string $filePath, string $filename = null, array $headers = []): StreamedResponse
    {
        $filename = $filename ?: basename($filePath);

        $response = new StreamedResponse(function () use ($filePath) {
            readfile($filePath);
        });

        $response->headers->add(array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $headers));

        return $response;
    }
}
