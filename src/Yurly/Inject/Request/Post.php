<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class Post extends RequestFoundation implements RequestInterface
{

    protected $post = [];

    /*
     * POST values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->type = 'Post';
        $this->post = $_POST;

    }

    /*
     * Return all properties as an array
     */
    public function toArray(): array
    {

        return $this->post;

    }

    /*
     * Magic getter method maps requests to the protected $post property
     */
    public function __get(string $property)
    {

        return (isset($this->post[$property]) ? $this->post[$property] : null);

    }

    /*
     * Magic isset method maps requests to the protected $post property
     */
    public function __isset(string $property): bool
    {

        return isset($this->post[$property]);

    }

}
