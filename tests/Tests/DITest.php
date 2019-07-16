<?php declare(strict_types=1);

namespace Tests;

use Yurly\Test\TestCase;

class DITest extends TestCase
{

    public function testSimpleDI()
    {

        $this->expectOutputString('TestsModelsInject' . 'Tests\Models\DI::__construct' . 'Tests\Controllers\DI::routeDefault');

        try {
            $this
                ->setProjectDefaults()
                ->setUrl('/di')
                ->callRoute();
        } catch (\Yurly\Core\Exception\RouteNotFoundException $e) {
            var_dump($this->getRouterMockLog());
        }

    }

    private function setProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Tests')
            ->setProjectPath('.');

    }

}
