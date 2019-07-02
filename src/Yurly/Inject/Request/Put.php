<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class Put extends RequestFoundation implements RequestInterface
{

    protected $put = [];

    /**
     * PUT values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->type = 'Put';

        parse_str(file_get_contents("php://input"), $this->put);

    }

    /**
     * Optional method to set the input manually
     */
    public function setProps(array $properties): void
    {

        $this->put = $properties;
        
    }

    /**
     * Return all properties as an array
     */
    public function toArray(): array
    {

        return $this->put;

    }

    /**
     * Magic getter method maps requests to the protected $put property
     */
    public function __get(string $property)
    {

        return (isset($this->put[$property]) ? $this->put[$property] : null);

    }

    /**
     * Magic isset method maps requests to the protected $put property
     */
    public function __isset(string $property): bool
    {

        return isset($this->put[$property]);

    }

}
