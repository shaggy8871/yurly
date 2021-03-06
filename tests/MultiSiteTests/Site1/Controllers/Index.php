<?php declare(strict_types=1);

namespace MultiSiteTests\Site1\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\Get;
use Yurly\Inject\Response\Json;
use Yurly\Inject\Response\Twig;

class Index extends Controller
{

    public function routeDefault(Get $request, Json $response): array
    {

        return ['result' => 'site1'];

    }

    public function routeTwigTest(Get $request, Twig $response): array
    {

        return ['result' => 'site1'];

    }

}
