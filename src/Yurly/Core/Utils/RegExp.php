<?php declare(strict_types=1);

/*
 * The RegExp helper is used to identify a variable as a regular expression
 */

namespace Yurly\Core\Utils;

use Yurly\Core\Url;

class RegExp
{

    protected $regExp;

    public function __construct(string $regExp)
    {

        $this->regExp = $regExp;

    }

    /*
     * Does the string value match?
     */
    public function matches(string $value): bool
    {

        return preg_match($this->regExp, $value) > 0;

    }

}
