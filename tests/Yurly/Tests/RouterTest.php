<?php declare(strict_types=1);

namespace Yurly\Tests;

use Yurly\Test\TestCase;
use Yurly\Core\{Caller, Project, Url, UrlFactory, Router};
use Yurly\Core\Exception\{
    ClassNotFoundException,
    RouteNotFoundException
};

class RouterTest extends TestCase
{

    private $router;

    public function setUp(): void
    {

        $this->router = new Router(new Project('www.testyurly.com', 'Yurly\\Tests', 'tests', true));

    }

    public function testUrlFactoryAutoDetect1()
    {

        // Instantiate test $_SERVER variables
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.testyurly.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = 'a=b';

        $url = UrlFactory::autodetect();

        // Make sure we get it back as expected
        $this->assertEquals($url->requestMethod, 'GET');
        $this->assertEquals($url->requestUri, '/');
        $this->assertEquals($url->rootUri, '');
        $this->assertEquals($url->rootBasePath, '');
        $this->assertEquals($url->scheme, 'http');
        $this->assertEquals($url->host, 'www.testyurly.com');
        $this->assertEquals($url->port, 80);
        $this->assertEquals($url->pathComponents, ['']);
        $this->assertEquals($url->queryString, 'a=b');

    }

    public function testUrlFactoryAutoDetect2()
    {

        // Instantiate test $_SERVER variables
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.testyurly.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/yurly/public/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = 'a=b';

        $url = UrlFactory::autodetect();

        // Test with getter methods
        $this->assertEquals($url->getRequestMethod(), 'GET');
        $this->assertEquals($url->getRequestUri(), '/');
        $this->assertEquals($url->getRootUri(), '/yurly/public/index.php');
        $this->assertEquals($url->getRootBasePath(), '/yurly/public');
        $this->assertEquals($url->getScheme(), 'http');
        $this->assertEquals($url->getHost(), 'www.testyurly.com');
        $this->assertEquals($url->getPort(), 80);
        $this->assertEquals($url->getPathComponents(), ['']);
        $this->assertEquals($url->getQueryString(), 'a=b');

    }

    public function testIndexRouteDefault()
    {

        $this->expectOutputString('RouteDefault');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/'));

    }

    public function testIndexRouteJsonResponse()
    {

        $this->expectOutputString(json_encode(['json' => true]));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/jsonResponse'));

    }

    public function testIndexRouteJsonpResponse()
    {

        $this->expectOutputString(sprintf('%s(%s)', 'jsonp', json_encode(['jsonp' => true])));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/jsonpResponse'));

    }

    public function testIndexRouteTwigResponse()
    {

        $this->expectOutputRegex("/RouteTwigResponseOkay/");

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/twigResponse'));

    }

    public function testIndexRouteUrlParamsRequest()
    {

        $this->expectOutputString(json_encode(['id' => '123', 'slug' => 'sluggish']));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlParamsRequest/123/sluggish'));

    }

    public function testIndexRouteUrlParamsRequestWithSpace()
    {

        $this->expectOutputString(json_encode(['id' => '123', 'slug' => 'sluggish spaces']));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlParamsRequest/123/sluggish%20spaces'));

    }

    public function testIndexRouteUrlParamsRequestWithPeriod()
    {

        $this->expectOutputString(json_encode(['id' => '123', 'slug' => '192.168.76.1']));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlParamsRequest/123/192.168.76.1'));

    }

    public function testProductsRouteDefault()
    {

        $this->expectOutputString('ProductsRouteDefault');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/products'));

    }

    public function testProductsRouteDefaultWithTrailingSlash()
    {

        $this->expectOutputString('ProductsRouteDefault');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/products/'));

    }

    public function testProductsRouteSubDir()
    {

        $this->expectOutputString('ProductsRouteSubDir');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/products/subdir'));

    }

    public function testProductsRouteSubDirWithTrailingSlash()
    {

        $this->expectOutputString('ProductsRouteSubDir');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/products/subdir/'));

    }

    public function testCaseTestRoute()
    {

        $this->expectOutputString('CaseTestRouteDefault');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/CaSetESt'));

    }

    public function testCaseTestRouteSubDir()
    {

        $this->expectOutputString('CaseTestRouteSubDir');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/CaSetESt/SuBDir'));

    }

    public function testCaseTestRouteNumbers()
    {

        $this->expectOutputString('CaseTestRouteNumbers99');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/CaSetESt/99'));

    }

    /******************************************************************
     * 
     * Testing: urlFor
     * 
     ******************************************************************/

    public function testUrlForArrayAsCallback()
    {

        $urlFor = $this->router->urlFor(['Yurly\\Tests\\Controllers\\Index', 'routeDefault']);

        $this->assertEquals($urlFor, '/');

    }

    public function testUrlForArrayAsCallbackWithParams()
    {

        $urlFor = $this->router->urlFor(['Yurly\\Tests\\Controllers\\Index', 'routeUrlParamsRequest'], ['id' => '123', 'slug' => 'test']);

        $this->assertEquals($urlFor, '/urlParamsRequest/123/test');

        $urlFor = $this->router->urlFor(['Yurly\\Tests\\Controllers\\Index', 'routeUrlParamsRequest'], ['id' => '123']);

        $this->assertEquals($urlFor, '/urlParamsRequest/123');

    }

    public function testUrlForIsCallback()
    {

        $urlFor = $this->router->urlFor('Index::routeDefault');

        $this->assertEquals($urlFor, '/');

    }

    public function testUrlForIsCallbackWithParams()
    {

        $urlFor = $this->router->urlFor('Yurly\\Tests\\Controllers\\Index::routeUrlParamsRequest', ['id' => '123', 'slug' => 'test']);
        $this->assertEquals($urlFor, '/urlParamsRequest/123/test');

        $urlFor = $this->router->urlFor('Yurly\\Tests\\Controllers\\Index::routeUrlParamsRequest', ['id' => '123']);
        $this->assertEquals($urlFor, '/urlParamsRequest/123');

    }

    /**
     * Fallback 1 is where we supply a Controller::method string and it gets matched using the project namespace
     */
    public function testUrlForFallback1()
    {

        $this->setProjectDefaults();

        // 1. Test router urlFor first
        $urlFor = $this->router->urlFor('Products::routeUrlParamsRequest', ['id' => '123', 'slug' => 'test']);
        $this->assertEquals($urlFor, '/products/urlParamsRequest/123/test');

        // 2. Test inside controller via URL
        $this->expectOutputString('/products/urlParamsRequest/123/test');
        $this->setUrl('/urlforfallback1')
             ->callRoute();

    }

    /**
     * Fallback 2 is where we have a Caller and it matches a method name only by using the existing controller
     */
    public function testUrlForFallback2()
    {

        $this->setProjectDefaults();

        // 1. Test router urlFor first; set caller for context
        $urlFor = $this->router->urlFor('routeUrlParamsRequest', ['id' => '123', 'slug' => 'test'], new Caller('Yurly\\Tests\\Controllers\\Index', 'routeUrlForFallback2'));
        $this->assertEquals($urlFor, '/urlParamsRequest/123/test');

        // 2. Test inside controller via URL
        $this->expectOutputString('/urlParamsRequest/123/test');
        $this->setUrl('/urlforfallback2')
             ->callRoute();

    }

    /**
     * Test getting the home page URL from the routeUrlForHome route using urlFor() in Router and ResponseFoundation
     */
    public function testUrlForFromInsideController()
    {

        $this->setProjectDefaults();

        // 1. Test router urlFor first; set caller for context
        $urlFor = $this->router->urlFor('routeDefault', [], new Caller('Yurly\\Tests\\Controllers\\Index', 'routeUrlForHome'));
        $this->assertEquals($urlFor, '/');

        $this->expectOutputString('/');

        // 2. Test inside controller via URL
        $this->setUrl('/urlforhome')
             ->callRoute();

    }

    public function testUrlForSuffix()
    {

        $this->expectOutputString('/urlDestination/val/suffix');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlForSuffix'));

    }

    public function testUrlForExtension()
    {

        $this->expectOutputString('/urlDestination/another-val.json');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlForExtension'));

    }

    public function testUrlForWithTwig()
    {

        $this->expectOutputRegex("/RouteTwigResponse: \/urlDestination\/val: \/products\/urlDestination\/val/");

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/twigUrlFor'));

    }

    public function testFlash()
    {

        $this->expectOutputString(json_encode(['with' => 'flash']));

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/flash'));

    }

    public function testModelsInjection()
    {

        $this->expectOutputString('TestsModelsInject');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/testsmodelsinject'));

    }

    public function testModelsInstantiateRequest()
    {

        $this->expectOutputString('TestsModelsInstantiateRequest');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/testsmodelsinstantiaterequest'));

    }

    public function testWithInvalidInjectionClass()
    {

        $this->expectException(ClassNotFoundException::class);

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/withInvalidInjectionClass'));

    }

    public function testAuthAllowed()
    {

        $this->expectOutputString('routeAuthOkay');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/auth/allowed'));

    }

    public function testAuthNotAllowed()
    {

        $this->expectOutputString('routeNotAuthorized');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/auth/notallowed'));

    }

    public function testAuthNotAllowedOutside()
    {

        $this->expectOutputString('routeIndexNotAuthorized');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/auth/notallowedoutside'));

    }

    public function testRouteResolverPath1()
    {

        $this->expectOutputString('ProductsRouteDefault');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/generic/123/product'));

    }

    public function testRouteResolverPath2()
    {

        $this->expectOutputString('ProductsRouteSubDir');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/123/product/finder'));

    }

    public function testRouteResolverPathFailure()
    {

        $this->expectException(RouteNotFoundException::class);

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/AAA/product/finder'));

    }

    public static function setUpBeforeClass(): void
    {

        if (!file_exists(__DIR__ . "/Views/cache")) {
            exec("mkdir " . __DIR__ . "/Views/cache");
            exec("chmod 777 " . __DIR__ . "/Views/cache");
        }

    }

    public static function tearDownAfterClass(): void
    {

        if (file_exists(__DIR__ . "/Views/cache")) {
            exec("rm -rf " . __DIR__ . "/Views/cache");
        }

    }

    /*
     * Construct a Url object using the supplied requestUri
     */
    private function generateUrl(string $requestUri): Url
    {

        $parsedUrl = parse_url($requestUri);

        return new Url([
            'host' => $parsedUrl['host'],
            'pathComponents' => explode('/', substr($parsedUrl['path'], 1)),
            'requestUri' => $parsedUrl['path'],
        ]);

    }

    private function setProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Yurly\\Tests')
            ->setProjectPath('tests');

    }

}
