<?php declare(strict_types=1);

namespace Yurly\Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Core\{Project, Init, Url, Router};

class CoreTest extends TestCase
{

    public function testSingleProject()
    {

        $this->expectOutputString('{"result":"site1"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.testyurly.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            'www.testyurly.com' => new Project('Site1', 'tests/Yurly/_sites/Site1', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testMultisiteSite1()
    {

        $this->expectOutputString('{"result":"site1"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.site1.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            'www.site1.com' => new Project('Site1', 'tests/Yurly/_sites/Site1', true),
            'www.site2.com' => new Project('Site2', 'tests/Yurly/_sites/Site2', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testMultisiteSite2()
    {

        $this->expectOutputString('{"result":"site2"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.site2.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            'www.site1.com' => new Project('Site1', 'tests/Yurly/_sites/Site1', true),
            'www.site2.com' => new Project('Site2', 'tests/Yurly/_sites/Site2', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

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
