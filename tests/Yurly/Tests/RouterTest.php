<?php declare(strict_types=1);

namespace Yurly\Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Core\Project;
use Yurly\Core\Url;
use Yurly\Core\UrlFactory;
use Yurly\Core\Router;
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

    public function testUrlFor()
    {

        $this->expectOutputString('/urlDestination/val');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlFor'));

    }

    public function testUrlForHome()
    {

        $this->expectOutputString('/');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlForHome'));

    }

    public function testUrlForAutodetect1()
    {

        $this->expectOutputString('/urldestinationautodetect');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlforautodetect1'));

    }

    public function testUrlForAutodetect2()
    {

        $this->expectOutputString('/products/urldestinationautodetect');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlforautodetect2'));

    }

    public function testUrlForAutodetect3()
    {

        $this->expectOutputString('routeUrlDestinationCanonical');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/differentName'));

    }

    public function testUrlForAutodetect4()
    {

        $this->expectOutputString('ProductsRouteUrlDestinationCanonical99');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/products/canonical/99'));

    }

    public function testUrlForFallback1()
    {

        $this->expectOutputString('/urlParamsRequest/123/slugger');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlForFallback1'));

    }

    public function testUrlForFallback2()
    {

        $this->expectOutputString('/products/urlDestination/val');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/urlForFallback2'));

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

}
