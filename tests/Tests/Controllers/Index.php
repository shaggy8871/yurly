<?php declare(strict_types=1);

namespace Tests\Controllers;

use Yurly\Core\Controller;
use Yurly\Inject\Request\{
    Get, 
    Post, 
    RouteParams
};
use Yurly\Inject\Response\{
    Html, 
    Json, 
    Jsonp, 
    Twig
};

class Index extends Controller
{

    public function routeDefault(): string
    {

        return "RouteDefault";

    }

    public function routeJsonResponse(Get $request, Json $response): array
    {

        return ['json' => true];

    }

    public function routeJsonResponseWithPost(Post $request, Json $response): array
    {

        return ['json' => $request->toArray()];

    }

    public function routeJsonpResponse(Get $request, Jsonp $response): array
    {

        $response->setCallback('jsonp');

        return ['jsonp' => true];

    }

    public function routeTwigResponse(Get $request, Twig $response): array
    {

        return ['twig' => 'Okay'];

    }

    /**
     * @canonical /urlParamsRequest/:id(/:slug)
     */
    public function routeUrlParamsRequest(RouteParams $request, Html $response): void
    {

        echo json_encode(['id' => $request->id, 'slug' => $request->slug]);

    }

    public function routeUrlFor(Get $request, Html $response): void
    {

        echo $response->urlFor([$this, 'routeUrlDestination'], [
            'var' => 'val'
        ]);

    }

    /**
     * @canonical /urlforwithparams/:id(/:slug)
     */
    public function routeUrlForWithParams(Get $request, Html $response): void
    {

        echo $response->urlFor([$this, 'routeUrlParamsRequest'], [
            'var' => 'val'
        ]);

    }

    /*
     * Swapped the order to test injection
     */
    public function routeUrlForHome(Html $response, Get $request): void
    {

        echo $response->urlFor('routeDefault');

    }

    public function routeUrlForFallback1(Get $request, Html $response): void
    {

        // Partial class name, should automatically namespace to current project
        echo $response->urlFor('Products::routeUrlParamsRequest', [
            'id' => '123',
            'slug' => 'test'
        ]);

    }

    public function routeUrlForFallback2(Get $request, Html $response): void
    {

        // Method name only, should use caller to determine URL path
        echo $response->urlFor('routeUrlParamsRequest', [
            'id' => '123',
            'slug' => 'test'
        ]);

    }

    public function routeUrlForSuffix(Get $request, Html $response): void
    {

        // Test suffix on URL
        echo $response->urlFor('routeUrlDestinationSuffix', [
            'var' => 'val'
        ]);

    }

    public function routeUrlForExtension(Get $request, Html $response, $test): void
    {

        // Test suffix on URL
        echo $response->urlFor('routeUrlDestinationExtension', [
            'var' => 'another-val'
        ]);

    }

    public function routeTwigUrlFor(Get $request, Twig $response): array
    {

        return [];

    }

    /*
     * Custom model injection test
     */
    public function routeTestsModelsInject(\Tests\Models\Inject $inject): void
    {

    }

    /*
     * Custom model instantiation test
     */
    public function routeTestsModelsInstantiateRequest(\Tests\Models\InstantiateRequest $request = null): void
    {

    }

    /*
     * Invalid class instantiation test
     */
    public function routeWithInvalidInjectionClass(NonExistentClass $request = null): void
    {

    }

    /**
     * @canonical /urlDestination/:var
     */
    public function routeUrlDestination(): void
    {

        // Used only for routeUrlFor tests

    }

    /**
     * @canonical /urlDestination/:var/suffix
     */
    public function routeUrlDestinationSuffix(): void
    {

        // Used only for routeUrlFor tests

    }

    /**
     * @canonical /urlDestination/:var.json
     */
    public function routeUrlDestinationExtension(): void
    {

        // Used only for routeUrlFor tests

    }

    public function routeUrlDestinationAutodetect(): void
    {

        // Used only for routeUrlForAutodetect tests

    }

    public function routeFlash(Get $request, Html $response): void
    {

        $response->flash('with', 'flash');

        echo $_SESSION['YURLY.flash'];

    }

    public function routeRedirect(Get $request, Html $response): void
    {

        $response->redirect('/');

    }

    public function routeProducts(): void
    {

        throw new Exception('Routing error, should route to Products::routeDefault');

    }

    public function routeNotAuthorized(): void
    {

        echo 'routeIndexNotAuthorized';

    }

    /**
     * NotFound handler
     */
    public function routeNotFound(Html $response): void
    {

        $response->setStatusCode(404);

    }

}
