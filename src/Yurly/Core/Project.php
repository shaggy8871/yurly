<?php declare(strict_types=1);

namespace Yurly\Core;

class Project
{

    const DEBUG_ON  = true;
    const DEBUG_OFF = false;

    protected $hosts;
    protected $ns;
    protected $debugMode;
    protected $path;
    protected $config;

    public function __construct($hosts, string $ns, string $path = '', bool $debugMode = self::DEBUG_OFF)
    {

        $configClass = $ns . '\\Config';

        $this->hosts = $hosts;
        $this->ns = $ns;
        $this->path = ($path ? realpath($path) . '/' : '') . str_replace('\\', '/', $ns);
        $this->debugMode = $debugMode;
        // Do we have a configuration class?
        $this->config = (class_exists($configClass) ? new $configClass($this) : new \stdClass());

    }

    /**
     * Handy accessor to saved project hosts
     */
    public function getHosts()
    {

        return $this->hosts;

    }

    /**
     * Handy accessor to saved project namespace
     */
    public function getNamespace(): string
    {

        return $this->ns;

    }

    /**
     * Handy accessor to saved path
     */
    public function getPath(): string
    {

        return $this->path;

    }

    /**
     * Handy accessor to saved debug mode value
     */
    public function getDebugMode(): bool
    {

        return $this->debugMode;

    }

    /**
     * Handy accessor to saved config class
     */
    public function getConfig()
    {

        return $this->config;

    }

    /**
     * Handy accessor to update debug mode value
     */
    public function setDebugMode(bool $debugMode): void
    {

        $this->debugMode = $debugMode;

    }

    /**
     * Magic getter method maps requests to some protected properties
     */
    public function __get(string $property)
    {

        return (in_array($property, ['hosts', 'ns', 'path', 'debugMode', 'config']) ?
            $this->$property : null);

    }

    /**
     * Returns true if some protected properties exist
     */
    public function __isset(string $property): bool
    {

        return (in_array($property, ['hosts', 'ns', 'path', 'debugMode', 'config']) ?
            property_exists($this, $property) : false);

    }

}
