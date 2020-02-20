<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Request\Request;

class ResponseTest extends TestCase
{
    public function testFlashWithMultipleKeys()
    {
        $project = new \Yurly\Core\Project(__NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project);
        $response = new \Yurly\Response\Response($ctx);

        $response->flash('first_key', 'first_value');
        $response->flash('second_key', 'second_value');

        $request = new Request($ctx);
        $this->assertEquals('first_value', $request->getFlash('first_key'));
        $this->assertEquals('second_value', $request->getFlash('second_key'));
    }

    public function testResponseInterface()
    {

        $this->expectOutputString('Hello world!');

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project);
        $response = new \Yurly\Inject\Response\Response($ctx);
        $response->setResponseClass(new \Yurly\Inject\Response\Html($ctx))
                 ->setViewParams('Hello world!')
                 ->render();

    }

    public function testJsonResponseInterface()
    {

        $this->expectOutputString('{"hello":"world!"}');

        $project = new \Yurly\Core\Project('', __NAMESPACE__, 'tests', true);
        $ctx = new \Yurly\Core\Context($project);
        $response = new \Yurly\Inject\Response\Response($ctx);
        $response->setResponseClass(new \Yurly\Inject\Response\Json($ctx))
                 ->setViewParams(['hello' => 'world!'])
                 ->render();

    }

}
