<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;
use Yurly\Core\Utils\Canonical;

class RouteParams extends RequestFoundation implements RequestInterface
{

    protected $routeParams = [];

    /*
     * Route param values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->type = 'RouteParams';

        if (isset($context->getCaller()->annotations['canonical'])) {
            $this->routeParams = Canonical::extract($context->getCaller()->annotations['canonical'], $context->getUrl()->requestUri);
        }

    }

    /*
     * Set the local route parameter variable
     */
    public function setRouteParams(array $routeParams): void
    {

        $this->routeParams = $routeParams;

    }

    /*
     * Return all properties as an array
     */
    public function toArray(): array
    {

        return $this->routeParams;

    }

    /*
     * Magic getter method maps requests to the protected $get property
     */
    public function __get(string $property)
    {

        return (isset($this->routeParams[$property]) ? $this->routeParams[$property] : null);

    }

    /*
     * Magic isset method maps requests to the protected $routeParams property
     */
    public function __isset(string $property): bool
    {

        return isset($this->routeParams[$property]);

    }

}
