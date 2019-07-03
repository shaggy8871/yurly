<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

interface RequestInterface
{

    public function hydrate(): void;
    public function toArray(): array;

}
