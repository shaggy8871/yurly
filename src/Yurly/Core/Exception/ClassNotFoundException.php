<?php declare(strict_types=1);

namespace Yurly\Core\Exception;

class ClassNotFoundException extends \Exception
{

    public function __construct(string $class, ?string $declaringClass, string $declaringMethod, int $code = 0, \Exception $previous = null)
    {

        parent::__construct(sprintf('Could not find type-hinted class for %s as referenced in %s.', $class, ($declaringClass ? $declaringClass . '::' : '') . $declaringMethod), $code, $previous);

    }

}
