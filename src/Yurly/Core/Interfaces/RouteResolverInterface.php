<?php declare(strict_types=1);

namespace Yurly\Core\Interfaces;

use Yurly\Core\{Project, Url};

interface RouteResolverInterface
{

    /*
     * The resolve method must respond with either of the following:
     * 1. A method name or string in the format $controller::$method if within a controller class
     * 2. A callable function or closure
     * 3. False, null or any other string indicates that the route was not resolved
     */
    function resolve(Project $project, Url $url);

}
