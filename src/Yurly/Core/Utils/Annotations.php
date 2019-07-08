<?php declare(strict_types=1);

namespace Yurly\Core\Utils;

class Annotations
{

    /**
     * Split docblock into annotations
     */
    public static function parseDocBlock($docComments)
    {

        $annotations = [];

        if (preg_match_all('#@(.*?)\n#s', $docComments, $components)) {
        	foreach($components[1] as $annotation) {
        	   list($key, $val) = preg_split('/[ :]+/', $annotation, 2);
        	   $annotations[$key] = trim($val);
        	}
        }

        return $annotations;

    }

}
