<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\{Context, Url};
use Yurly\Core\Exception\UnknownPropertyException;

abstract class RequestFoundation
{

    protected $props = [];
    protected $context;
    protected $type;
    protected $flash;

    public function __construct(Context $context)
    {

        $this->context = $context;

        // Attempt to guess the type
        if ($this->context->getUrl() && $this->context->getUrl()->requestMethod) {
            $this->type = ucfirst(strtolower($this->context->getUrl()->requestMethod));
        } else {
            $this->type = 'Unknown';
        }

        // Remove flash from session if available
        if (isset($_SESSION['YURLY.flash'])) {
            $this->flash = json_decode($_SESSION['YURLY.flash']);
            unset($_SESSION['YURLY.flash']);
        }

    }

    /**
     * Manually set props
     */
    public function setProps(array $props): void
    {

        $this->props = $props;

    }

    /**
     * Returns the type of request
     */
    public function getType(): string
    {

        return $this->type;

    }

    /**
     * Returns the saved context
     */
    public function getContext(): Context
    {

        return $this->context;

    }

    /**
     * Handy accessor to get the URL straight from the context
     */
    public function getUrl(): Url
    {

        return $this->context->getUrl();

    }

    /**
     * Look up the saved Flash value if available
     */
    public function getFlash(string $key)
    {

        return (($this->flash) && (property_exists($this->flash, $key)) ? $this->flash->$key : null);

    }

    /**
    * Return all public and protected values
    */
    public function __get(string $property)
    {

        if (!property_exists($this, $property)) {
            throw new UnknownPropertyException($property, __CLASS__);
        }

        $reflect = new \ReflectionProperty($this, $property);
        if (!$reflect->isPrivate()) {
            return $this->$property;
        }

    }

    /**
    * Returns true if the property is set
    */
    public function __isset(string $property): bool
    {

        if (!property_exists($this, $property)) {
            throw new UnknownPropertyException($property, __CLASS__);
        }

        $reflect = new \ReflectionProperty($this, $property);
        if ((!$reflect->isPrivate()) && ($this->property)) {
            return true;
        }

    }

}
