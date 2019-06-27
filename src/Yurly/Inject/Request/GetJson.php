<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class GetJson extends Get implements RequestInterface
{

    /*
     * GET values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->get = json_decode($_SERVER['QUERY_STRING'], true);

    }

}
