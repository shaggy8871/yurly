<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class Delete extends RequestFoundation implements RequestInterface
{

    protected $delete = [];

    /**
     * DELETE values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->type = 'Delete';

        parse_str(file_get_contents("php://input"), $this->delete);

    }

    /**
     * Optional method to set the input manually
     */
    public function setProps(array $properties): void
    {

        $this->delete = $properties;
        
    }

    /**
     * Return all properties as an array
     */
    public function toArray(): array
    {

        return $this->delete;

    }

    /**
     * Magic getter method maps requests to the protected $delete property
     */
    public function __get(string $property)
    {

        return (isset($this->delete[$property]) ? $this->delete[$property] : null);

    }

    /**
     * Magic isset method maps requests to the protected $delete property
     */
    public function __isset(string $property): bool
    {

        return isset($this->delete[$property]);

    }

}
