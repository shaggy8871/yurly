<?php declare(strict_types=1);

namespace Tests;

use Yurly\Test\TestCase;
use Yurly\Test\Exception\MismatchedParametersException;
use Yurly\Inject\Request\{Get, Post, Request, RouteParams};
use Yurly\Inject\Response\{Html, Json};

class TestCaseTest extends TestCase
{

    public function testGetRoute()
    {

        $route = $this
            ->setProjectDefaults()
            ->setUrl('/')
            ->getRoute();

        $this->assertEquals($route, 'Tests\\Controllers\\Index::routeDefault');

    }

    public function testGetCaller()
    {

        $caller = $this
            ->setProjectDefaults()
            ->setUrl('/twigResponse')
            ->getCaller();

        $this->assertInstanceOf(\Yurly\Core\Caller::class, $caller);
        $this->assertEquals(get_class($caller->getController()), 'Tests\\Controllers\\Index');
        $this->assertEquals($caller->getMethod(), 'routeTwigResponse');

    }

    public function testCallRoute()
    {

        $this->expectOutputString('RouteDefault');

        $this
            ->setProjectDefaults()
            ->setUrl('/')
            ->callRoute();

    }

    public function testGetRouteResponse()
    {

        $response = $this
            ->setProjectDefaults()
            ->setUrl('/twigResponse')
            ->getRouteResponse();

        $this->assertEquals($response, ['twig' => 'Okay']);

    }

    public function testCallRouteWithRequestMock()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/urlParamsRequest/123/test');

        $mockRequest = $this
            ->getRequestMock(RouteParams::class, function(RouteParams $self) {
                $self->setProps(['id' => '456', 'slug' => 'hydrated']);
            });

        $this->expectOutputString(json_encode(['id' => '456', 'slug' => 'hydrated']));

        $this
            ->callRouteWithMocks([
                RouteParams::class => $mockRequest
            ]);

    }

    public function testCallRouteWithResponseMock()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/jsonResponse');

        $mockResponse = $this
            ->getResponseMock(Json::class, function(array $params) {
                $this->assertEquals($params, ['json' => true]);
            });

        $this
            ->callRouteWithMocks([
                Json::class => $mockResponse
            ]);

        $mockResponse->assertOk();
        $mockResponse->assertContentType('application/json');

    }

    public function testCallRouteWithRequestAndResponseMock()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/jsonResponseWithPost');

        $mockRequest = $this
            ->getRequestMock(Post::class, function(Post $self) {
                $self->setProps(['id' => '123', 'slug' => 'hydrated']);
            });

        $mockResponse = $this
            ->getResponseMock(Json::class, function(array $params) {
                $this->assertEquals($params, ['json' => ['id' => '123', 'slug' => 'hydrated']]);
            });

        $this
            ->callRouteWithMocks([
                Post::class => $mockRequest,
                Json::class => $mockResponse
            ]);

        $mockResponse->assertOk();
        $mockResponse->assertContentType('application/json');

    }

    public function testCallRouteWithMismatchedRequestMock()
    {

        $this->expectException(MismatchedParametersException::class);

        $this
            ->setProjectDefaults()
            ->setUrl('/jsonResponseWithPost');

        $mockRequest = $this
            ->getRequestMock(Get::class, function(Get $self) {
                $self->setProps(['id' => '123', 'slug' => 'hydrated']);
            });

        $this
            ->callRouteWithMocks([
                Get::class => $mockRequest
            ]);

    }

    public function testCallRouteWithRequestTypeProps()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/jsonResponseWithRequest');

        $mockRequest = $this
            ->getRequestMock(Request::class, function(Request $self) {
                $self->setTypeProps(Request::TYPE_POST, ['id' => '123', 'slug' => 'post']);
                $self->setTypeProps(Request::TYPE_GET,  ['id' => '456', 'slug' => 'get']);
            });

        $mockResponse = $this
            ->getResponseMock(Json::class, function(array $params) {
                $this->assertEquals($params, [
                    'post' => ['id' => '123', 'slug' => 'post'],
                    'get'  => ['id' => '456', 'slug' => 'get'],
                ]);
            });

        $this
            ->callRouteWithMocks([
                Request::class => $mockRequest,
                Json::class => $mockResponse
            ]);

    }

    public function testCallRouteWithRedirect()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/redirect');

        $mockResponse = $this
            ->getResponseMock(Html::class);

        $this
            ->callRouteWithMocks([
                Html::class => $mockResponse
            ]);

        $mockResponse->assertRedirect('/');
        $mockResponse->assertStatusCode(302);

    }

    public function testResponseWithAssertNotFound()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/notFound');

        $mockResponse = $this
            ->getResponseMock(Html::class);

        $this
            ->callRouteWithMocks([
                Html::class => $mockResponse
            ]);

        $mockResponse->assertNotFound();

    }

    public function testSetUrlFor()
    {

        $this->setProjectDefaults()
             ->setUrlFor(['Tests\\Controllers\\Index', 'routeJsonResponse']);

        $responseResult = $this->getRouteResponse();

        $this->assertEquals($responseResult, ['json' => true]);

    }

    private function setProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Tests')
            ->setProjectPath('tests');

    }

}
