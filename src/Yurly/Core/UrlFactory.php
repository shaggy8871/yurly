<?php declare(strict_types=1);

namespace Yurly\Core;

/*
 * Create a Url by auto-detecting values
 */

class UrlFactory
{

    /**
     * Autodetect URL settings and return a Url object
     */
    public static function autodetect(?array $serverParams = null): Url
    {

        $serverParams = $serverParams ?? $_SERVER;

        // Basic lookup
        $requestMethod = $serverParams['REQUEST_METHOD'];
        $scheme = isset($serverParams['HTTPS']) ? 'https' : 'http';
        $host = $serverParams['HTTP_HOST'];
        $port = $serverParams['SERVER_PORT'];

        // Determine the script filename so we can exclude it from the parsed path
        $scriptFilename = basename($serverParams['SCRIPT_FILENAME']);
        // Determine the correct request Uri
        $requestUri = $serverParams['YURLY_REQUEST_URI'] ?? $serverParams['PATH_INFO'] ?? $serverParams['REQUEST_URI'];
        // Exclude query string
        if (strpos($requestUri, '?') !== false) {
            $requestUri = strstr($requestUri, '?', true);
        }

        $rootBasePath = (isset($serverParams['SCRIPT_NAME']) ? rtrim(dirname($serverParams['SCRIPT_NAME']), '/') : '/');
        $rootUri = ($rootBasePath == '' ? '' : (isset($serverParams['SCRIPT_NAME']) ? rtrim($serverParams['SCRIPT_NAME'], '/') : '/'));

        $pathParsed = parse_url($scheme . '://' . $host . $requestUri . (isset($serverParams['QUERY_STRING']) ? '?' . $serverParams['QUERY_STRING'] : ''));
        $pathComponents = explode('/', substr($pathParsed['path'], 1));
        $queryString = (isset($pathParsed['query']) ? $pathParsed['query'] : '');

        // Send back complete Url object
        return new Url([
            'requestMethod' => $requestMethod,
            'requestUri' => $requestUri,
            'rootUri' => $rootUri,
            'rootBasePath' => $rootBasePath,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'pathComponents' => $pathComponents,
            'queryString' => $queryString
        ]);

    }

}
