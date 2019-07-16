<?php declare(strict_types=1);

namespace Tests;

use Yurly\Test\TestCase;

class DITest extends TestCase
{

    public function testSimpleDI()
    {

        $this->expectOutputString('TestsModelsInject' . 'Tests\Models\DI::__construct' . 'Tests\Controllers\DI::routeDefault');

        $this
            ->setProjectDefaults()
            ->setUrl('/di')
            ->callRoute();

    }

    private function setProjectDefaults(): self
    {

        return $this
            ->setProjectNamespace('Tests')
            ->setProjectPath('tests');

    }

}
