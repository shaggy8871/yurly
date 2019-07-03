<?php declare(strict_types=1);

namespace Yurly\Tests;

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

        $this->assertEquals($route, 'Yurly\\Tests\\Controllers\\Index::routeDefault');

    }

    public function testGetCaller()
    {

        $caller = $this
            ->setProjectDefaults()
            ->setUrl('/twigresponse')
            ->getCaller();

        $this->assertInstanceOf(\Yurly\Core\Caller::class, $caller);
        $this->assertEquals(get_class($caller->getController()), 'Yurly\\Tests\\Controllers\\Index');
        $this->assertEquals($caller->getMethod(), 'routeTwigresponse');

    }

    public function testCallRoute()
    {

        $this->expectOutputString('RouteDefault');

        $this
            ->setProjectDefaults()
            ->callRoute('/');

    }

    public function testCallRouteWithRequestMock()
    {

        $this
            ->setProjectDefaults();

        $requestProps = ['id' => '456', 'slug' => 'hydrated'];

        $mockRequest = $this
            ->getRequestMock(RouteParams::class, function($self) use ($requestProps) { $self->setProps($requestProps); });

        $this->expectOutputString(json_encode(['id' => '456', 'slug' => 'hydrated']));

        $this
            ->callRouteWithMocks('/urlParamsRequest/123/test', [
                RouteParams::class => $mockRequest
            ]);

    }

    public function testCallRouteWithResponseMock()
    {

        $this
            ->setProjectDefaults();

        $responseResult = [];

        $mockResponse = $this
            ->getResponseMock(Json::class, function($params) use (&$responseResult) { $responseResult = $params; });

        $this
            ->callRouteWithMocks('/jsonresponse', [
                Json::class => $mockResponse
            ]);

        $this->assertEquals($responseResult, ['json' => true]);

    }

    private function setProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Yurly\\Tests')
            ->setProjectPath('tests');

    }

}
