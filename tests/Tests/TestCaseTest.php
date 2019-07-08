<?php declare(strict_types=1);

namespace Tests;

use Yurly\Test\TestCase;
use Yurly\Inject\Request\RouteParams;
use Yurly\Inject\Response\Json;

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
            ->setUrl('/twigresponse')
            ->getCaller();

        $this->assertInstanceOf(\Yurly\Core\Caller::class, $caller);
        $this->assertEquals(get_class($caller->getController()), 'Tests\\Controllers\\Index');
        $this->assertEquals($caller->getMethod(), 'routeTwigresponse');

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
            ->setUrl('/twigresponse')
            ->getRouteResponse();

        $this->assertEquals($response, ['twig' => 'Okay']);

    }

    public function testCallRouteWithRequestMock()
    {

        $this
            ->setProjectDefaults()
            ->setUrl('/urlParamsRequest/123/test');

        $mockRequest = $this
            ->getRequestMock(RouteParams::class, function($self) {
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
            ->setUrl('/jsonresponse');

        $mockResponse = $this
            ->getResponseMock(Json::class, function($params) {
                $this->assertEquals($params, ['json' => true]);
            });

        $this
            ->callRouteWithMocks([
                Json::class => $mockResponse
            ]);

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
