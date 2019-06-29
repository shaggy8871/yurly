<?php declare(strict_types=1);

namespace Yurly\Tests;

use Yurly\Core\{Project, Url};
use Yurly\Core\Interfaces\RouteResolverInterface;
use Yurly\Core\Utils\RegExp;

class RouteResolver implements RouteResolverInterface
{

    public function resolve(Project $project, Url $url)
    {

        $routes = [
            [
                'match' => new RegExp('/^\/generic\/[a-z0-9]+\/product\/?$/'),
                'route' => 'Products::routeDefault',
            ],
            [
                'match' => new RegExp('/^\/[a-z0-9]+\/product\/finder\/?$/'),
                'route' => 'Products::routeSubDir',
            ],
        ];

        foreach($routes as $route) {
            if ($route['match']->matches($url->requestUri)) {
                return $route['route'];
            }
        }

    }

}