<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

interface ResponseInterface
{

    public function render($params = null): void;

}
