<?php declare(strict_types=1);

namespace Tests\Controllers;

use Yurly\Core\Controller;
use Yurly\Core\Url;
use Yurly\Inject\Request\RouteParams;

class CaSetEst extends Controller
{

    public function routeDefault(): string
    {

        return "CaseTestRouteDefault";

    }

    public function routeSubDir(): string
    {

        return "CaseTestRouteSubDir";

    }

    /**
     * @canonical /casetest/:id
     */
    public function routeNumbers(RouteParams $request): string
    {

        return "CaseTestRouteNumbers" . (string) $request->id;

    }

}
