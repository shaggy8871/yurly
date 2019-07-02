<?php declare(strict_types=1);

namespace Yurly\Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Core\{Project, Url, Context, Caller};
use Yurly\Core\Exception\URLParseException;

class RequestTest extends TestCase
{

    public function testGetIsset()
    {

        // Instantiate test variables
        parse_str('var1=1&var2=two', $_GET);

        $project = new Project('', __NAMESPACE__, 'tests', true);
        $url = $this->generateUrl('/');
        $ctx = new Context($project, $url);
        $request = new \Yurly\Inject\Request\Get($ctx);

        $this->assertEquals(isset($request->var1), true);
        $this->assertEquals($request->var1, 1);
        $this->assertEquals(isset($request->var2), true);
        $this->assertEquals($request->var2, 'two');
        $this->assertEquals(isset($request->var3), false);
        $this->assertEquals($request->var3, null);

    }

    public function testPostIsset()
    {

        // Instantiate test variables
        parse_str('var1=1&var2=two', $_POST);

        $project = new Project('', __NAMESPACE__, 'tests', true);
        $url = $this->generateUrl('/');
        $ctx = new Context($project, $url);
        $request = new \Yurly\Inject\Request\Post($ctx);

        $this->assertEquals(isset($request->var1), true);
        $this->assertEquals($request->var1, 1);
        $this->assertEquals(isset($request->var2), true);
        $this->assertEquals($request->var2, 'two');
        $this->assertEquals(isset($request->var3), false);
        $this->assertEquals($request->var3, null);

    }

    public function testPutIsset()
    {

        $project = new Project('', __NAMESPACE__, 'tests', true);
        $url = $this->generateUrl('/');
        $ctx = new Context($project, $url);
        $request = new \Yurly\Inject\Request\Put($ctx);
        $request->setProps([
            'var1' => 1,
            'var2' => 'two'
        ]);

        $this->assertEquals(isset($request->var1), true);
        $this->assertEquals($request->var1, 1);
        $this->assertEquals(isset($request->var2), true);
        $this->assertEquals($request->var2, 'two');
        $this->assertEquals(isset($request->var3), false);
        $this->assertEquals($request->var3, null);

    }

    public function testDeleteIsset()
    {

        $project = new Project('', __NAMESPACE__, 'tests', true);
        $url = $this->generateUrl('/');
        $ctx = new Context($project, $url);
        $request = new \Yurly\Inject\Request\Delete($ctx);
        $request->setProps([
            'var1' => 1,
            'var2' => 'two'
        ]);

        $this->assertEquals(isset($request->var1), true);
        $this->assertEquals($request->var1, 1);
        $this->assertEquals(isset($request->var2), true);
        $this->assertEquals($request->var2, 'two');
        $this->assertEquals(isset($request->var3), false);
        $this->assertEquals($request->var3, null);

    }

    public function testRouteParamsIsset()
    {

        $url = $this->generateUrl('/test/1/two');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:var1/:var2'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->var1), true);
        $this->assertEquals($request->var1, 1);
        $this->assertEquals(isset($request->var2), true);
        $this->assertEquals($request->var2, 'two');
        $this->assertEquals(isset($request->var3), false);
        $this->assertEquals($request->var3, null);

    }

    public function testRouteParamsUnparsableCanonical()
    {

        $this->expectException(URLParseException::class);

        $url = $this->generateUrl('/test/param1/param2');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/$$var1--'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

    }

    public function testRouteParamsNoLeadingSlash()
    {

        $this->expectException(URLParseException::class);

        $url = $this->generateUrl('/one/two/three');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => ':one/:two/:three'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

    }

    public function testRouteParamsCharacterRange()
    {

        $url = $this->generateUrl('/test/one/two/three/');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:withUpper/:with_underscore/:withNum1(/:notHere)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->withUpper), true);
        $this->assertEquals($request->withUpper, 'one');
        $this->assertEquals(isset($request->with_underscore), true);
        $this->assertEquals($request->with_underscore, 'two');
        $this->assertEquals(isset($request->withNum1), true);
        $this->assertEquals($request->withNum1, 'three');
        $this->assertEquals(isset($request->notHere), false);
        $this->assertEquals($request->notHere, null);

    }

    public function testRouteParamsLongPrefix()
    {

        $url = $this->generateUrl('/longer/prefix/test/one/two/three');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/longer/prefix/test/:one/:two/:three'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->one), true);
        $this->assertEquals($request->one, 'one');
        $this->assertEquals(isset($request->two), true);
        $this->assertEquals($request->two, 'two');
        $this->assertEquals(isset($request->three), true);
        $this->assertEquals($request->three, 'three');

    }

    public function testRouteParamsOptionalFirst()
    {

        $url = $this->generateUrl('/test');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test(/:optional)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->optional), false);
        $this->assertEquals($request->optional, null);

    }

    public function testRouteParamsMandatoryFirstOptionalSecond()
    {

        $url = $this->generateUrl('/test');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        // Should be empty because canonical URL doesn't match
        $this->assertEquals(empty($request->toArray()), true);

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndEmbeddedThirdWithOneParam()
    {

        $url = $this->generateUrl('/test/mandatory');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1(/:optional2))'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), false);
        $this->assertEquals($request->optional1, null);
        $this->assertEquals(isset($request->optional2), false);
        $this->assertEquals($request->optional2, null);

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndEmbeddedThirdWithTwoParams()
    {

        $url = $this->generateUrl('/test/mandatory/optional1');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1(/:optional2))'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), true);
        $this->assertEquals($request->optional1, 'optional1');
        $this->assertEquals(isset($request->optional2), false);
        $this->assertEquals($request->optional2, null);

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndEmbeddedThirdWithAllParams()
    {

        $url = $this->generateUrl('/test/mandatory/optional1/optional2');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1(/:optional2))'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), true);
        $this->assertEquals($request->optional1, 'optional1');
        $this->assertEquals(isset($request->optional2), true);
        $this->assertEquals($request->optional2, 'optional2');

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndIsolatedThirdWithOneParam()
    {

        $url = $this->generateUrl('/test/mandatory');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1)(/:optional2)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), false);
        $this->assertEquals($request->optional1, null);
        $this->assertEquals(isset($request->optional2), false);
        $this->assertEquals($request->optional2, null);

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndIsolatedThirdWithTwoParams()
    {

        $url = $this->generateUrl('/test/mandatory/optional1');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1)(/:optional2)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), true);
        $this->assertEquals($request->optional1, 'optional1');
        $this->assertEquals(isset($request->optional2), false);
        $this->assertEquals($request->optional2, null);

    }

    public function testRouteParamsMandatoryFirstOptionalSecondAndIsolatedThirdWithAllParams()
    {

        $url = $this->generateUrl('/test/mandatory/optional1/optional2');
        $caller = new Caller('Test', 'routeTest', [
            'canonical' => '/test/:mandatory(/:optional1)(/:optional2)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->mandatory), true);
        $this->assertEquals($request->mandatory, 'mandatory');
        $this->assertEquals(isset($request->optional1), true);
        $this->assertEquals($request->optional1, 'optional1');
        $this->assertEquals(isset($request->optional2), true);
        $this->assertEquals($request->optional2, 'optional2');

    }

    public function testRouteParamsWithPathPlaceholder()
    {

        $url = $this->generateUrl('/list/1/items/2');
        $caller = new Caller('Test', 'routeList', [
            'canonical' => '/list(/:id)/items(/:page)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->id), true);
        $this->assertEquals($request->id, '1');
        $this->assertEquals(isset($request->page), true);
        $this->assertEquals($request->page, '2');

    }

    public function testRouteParamsWithPathPlaceholderFirstVar()
    {

        $url = $this->generateUrl('/list/1/items');
        $caller = new Caller('Test', 'routeList', [
            'canonical' => '/list(/:id)/items(/:page)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->id), true);
        $this->assertEquals($request->id, '1');
        $this->assertEquals(isset($request->page), false);
        $this->assertEquals($request->page, null);

    }

    public function testRouteParamsWithPathPlaceholderSecondVar()
    {

        $url = $this->generateUrl('/list/items/2');
        $caller = new Caller('Test', 'routeList', [
            'canonical' => '/list(/:id)/items(/:page)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        $this->assertEquals(isset($request->id), false);
        $this->assertEquals($request->id, null);
        $this->assertEquals(isset($request->page), true);
        $this->assertEquals($request->page, '2');

    }

    public function testRouteParamsWithPathPlaceholderNotFound()
    {

        $url = $this->generateUrl('/list/1');
        $caller = new Caller('Test', 'routeList', [
            'canonical' => '/list(/:id)/items(/:page)'
        ]);

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project, $url, $caller);
        $request = new \Yurly\Inject\Request\RouteParams($ctx);

        // Should be empty because canonical URL doesn't match
        $this->assertEquals(empty($request->toArray()), true);

    }

    /*
     * Construct a Url object using the supplied requestUri
     */
    private function generateUrl(string $requestUri): Url
    {

        $pathComponents = explode('/', substr($requestUri, 1));

        return new Url([
            'pathComponents' => $pathComponents,
            'requestUri' => $requestUri
        ]);

    }

}
