<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

use Yurly\Core\Context;

class GetJson extends Get implements RequestInterface
{

    public function hydrate(array $props = []): void
    {

        $this->props = json_decode($this->context->getUrl()->queryString, true) ?? [];

    }

}
