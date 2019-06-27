<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class PostJson extends Post implements RequestInterface
{

    /*
     * POST values are simply stored as object properties - unsanitized!
     */
    public function __construct(Context $context)
    {

        parent::__construct($context);

        $this->post = json_decode(file_get_contents('php://input'), true);

    }

}
