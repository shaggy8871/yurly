<?php declare(strict_types=1);

namespace Yurly\Core\Exception;

class UnknownPropertyException extends \Exception
{

    public function __construct(string $property, string $class, int $code = 0, \Exception $previous = null)
    {

        parent::__construct(sprintf('Unknown property %s in %s.', $property, $class), $code, $previous);

    }

}
