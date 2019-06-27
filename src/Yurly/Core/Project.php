<?php declare(strict_types=1);

namespace Yurly\Core;

class Project
{

    protected $ns;
    protected $debugMode;
    protected $path;
    protected $config;
    protected $services;

    public function __construct(string $ns, string $path = '', bool $debugMode = false)
    {

        $configClass = $ns . '\\Config';

        $this->ns = $ns;
        $this->path = ($path ? realpath($path) . '/' : '') . str_replace('\\', '/', $ns);
        $this->debugMode = $debugMode;
        // Do we have a configuration class?
        $this->config = (class_exists($configClass) ? new $configClass($this) : new \stdClass());

    }

    /*
     * Handy accessor to saved project namespace
     */
    public function getNamespace(): string
    {

        return $this->ns;

    }

    /*
     * Handy accessor to saved path
     */
    public function getPath(): string
    {

        return $this->path;

    }

    /*
     * Handy accessor to saved debug mode value
     */
    public function getDebugMode(): bool
    {

        return $this->debugMode;

    }

    /*
     * Handy accessor to saved config class
     */
    public function getConfig()
    {

        return $this->config;

    }

    /*
     * Add a public service to the project
     */
    public function addService(string $name, $object): void
    {

        $this->services[$name] = $object;

    }

    /*
     * Returns the public service
     */
    public function getService(string $name)
    {

        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

    }

    /*
     * Magic getter method maps requests to some protected properties
     */
    public function __get(string $property)
    {

        return (in_array($property, ['ns', 'path', 'debugMode', 'config']) ?
            $this->$property : null);

    }

    /*
     * Returns true if some protected properties exist
     */
    public function __isset(string $property): bool
    {

        return (in_array($property, ['ns', 'path', 'debugMode', 'config']) ?
            property_exists($this, $property) : false);

    }

}
