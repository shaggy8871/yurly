<?php declare(strict_types=1);

namespace Yurly\Core;

/*
 * The base Url class stores the URL in workable components
 */

use Yurly\Core\Exception\UnknownPropertyException;

class Url
{

    protected $requestMethod;
    protected $requestUri;
    protected $rootUri;
    protected $rootBasePath;
    protected $scheme;
    protected $host;
    protected $port;
    protected $pathComponents;
    protected $queryString;

    public function __construct(array $urlComponents)
    {

        $this->requestMethod = $urlComponents['requestMethod'] ?? null;
        $this->requestUri = $urlComponents['requestUri'] ?? null;
        $this->rootUri = $urlComponents['rootUri'] ?? null;
        $this->rootBasePath = $urlComponents['rootBasePath'] ?? null;
        $this->scheme = $urlComponents['scheme'] ?? null;
        $this->host = $urlComponents['host'] ?? null;
        $this->port = $urlComponents['port'] ?? null;
        $this->pathComponents = $urlComponents['pathComponents'] ?? null;
        $this->queryString = $urlComponents['queryString'] ?? null;

    }

    /*
     * Magic getter prevents class values from being overwritten
     */
    public function __get(string $property)
    {

        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new UnknownPropertyException($property, __CLASS__);
        }

    }

    /*
     * Activate getProperty() style method calls
     */
    public function __call(string $name, array $args)
    {

        if (substr($name, 0, 3) == 'get') {
            $property = lcfirst(substr($name, 3));
            if (property_exists($this, $property)) {
                return $this->$property;
            } else {
                throw new Exception\UnknownPropertyException($property, __CLASS__);
            }
        }

    }

    /*
     * Returns true if the property exists
     */
    public function __isset(string $property): bool
    {

        return property_exists($this, $property);

    }

}
