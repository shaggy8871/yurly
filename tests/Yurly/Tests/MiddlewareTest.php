<?php declare(strict_types=1);

namespace Yurly\Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Core\{Project, Url, Router};

class MiddlewareTest extends TestCase
{

    public function setUp(): void
    {

        $this->router = new Router(new Project('www.testyurly.com', 'Yurly\\Tests', 'tests', true));

    }

    public function testTwoBeforeHandlers()
    {

        $this->expectOutputString('beforeAllRoutesbeforeHandler1beforeHandler2afterAllRoutesFinal');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/middleware/beforeHandlerTest'));

    }

    public function testTwoBeforeHandlersWithStop()
    {

        $this->expectOutputString('beforeAllRoutesBeforeStoppedafterAllRoutesFinal');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/middleware/beforeHandlerTestWithStop'));

    }

    public function testTwoAfterHandlers()
    {

        $this->expectOutputString('beforeAllRoutesafterAllRoutesafterHandler1afterHandler2afterHandler2');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/middleware/afterHandlerTest'));

    }

    public function testTwoAfterHandlersWithStop()
    {

        $this->expectOutputString('beforeAllRoutesafterAllRoutesAfterStoppedafterStop');

        $this->router->parseUrl($this->generateUrl('http://www.testyurly.com/middleware/afterHandlerTestWithStop'));

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
