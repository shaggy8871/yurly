<?php declare(strict_types=1);

namespace Yurly\Tests;

use Yurly\Test\TestCase;

class TestCaseTest extends TestCase
{

    public function testGetRoute()
    {

        $route = $this
            ->getProjectDefaults()
            ->setUrl('/')
            ->getRoute();

        $this->assertEquals($route, 'Yurly\\Tests\\Controllers\\Index::routeDefault');

    }

    public function testGetCaller()
    {

        $caller = $this
            ->getProjectDefaults()
            ->setUrl('/twigresponse')
            ->getCaller();

        $this->assertInstanceOf(\Yurly\Core\Caller::class, $caller);
        $this->assertEquals(get_class($caller->getController()), 'Yurly\\Tests\\Controllers\\Index');
        $this->assertEquals($caller->getMethod(), 'routeTwigresponse');

    }

    private function getProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Yurly\\Tests')
            ->setProjectPath('tests');

    }

}
