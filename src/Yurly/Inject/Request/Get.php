<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class Get extends RequestFoundation implements RequestInterface
{

    protected $get = [];

    /**
     * GET values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->type = 'Get';
        $this->get = $_GET;

    }

    /**
     * Return all properties as an array
     */
    public function toArray(): array
    {

        return $this->get;

    }

    /**
     * Magic getter method maps requests to the protected $get property
     */
    public function __get(string $property)
    {

        return (isset($this->get[$property]) ? $this->get[$property] : null);

    }

    /**
     * Magic isset method maps requests to the protected $get property
     */
    public function __isset(string $property): bool
    {

        return isset($this->get[$property]);

    }

}
