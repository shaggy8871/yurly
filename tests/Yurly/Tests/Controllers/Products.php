<?php declare(strict_types=1);

namespace Yurly\Tests\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\RouteParams;

class Products extends Controller
{

    public function routeDefault(): string
    {

        return "ProductsRouteDefault";

    }

    public function routeSubDir(): string
    {

        return "ProductsRouteSubDir";

    }

    /**
     * @canonical /products/urlDestination/:var
     */
    public function routeUrlDestination(): void
    {

        // Used only for routeUrlFor tests

    }

    /**
     * @canonical /products/canonical/:id
     */
    public function routeUrlDestinationCanonical(RouteParams $request): void
    {

        echo 'ProductsRouteUrlDestinationCanonical' . $request->id;

    }

    public function routeUrlDestinationAutodetect(): void
    {

        // Used only for routeUrlForAutodetect tests

    }

}
