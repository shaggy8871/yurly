<?php declare(strict_types=1);

namespace Yurly\Core\Interfaces;

use Yurly\Core\{Project, Url};

interface RouteResolverInterface
{

    /*
     * The resolve method must respond with either of the following:
     * 1. A method name or string in the format $controller::$method if within a controller class
     * 2. False or null to indicate that no route was found
     */
    function resolve(Project $project, Url $url);

}
