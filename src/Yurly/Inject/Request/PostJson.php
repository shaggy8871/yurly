<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class PostJson extends Post implements RequestInterface
{

    public function hydrate(): void
    {

        $this->props = json_decode(file_get_contents('php://input'), true) ?? [];

    }

}
