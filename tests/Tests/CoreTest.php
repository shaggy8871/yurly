<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yurly\Core\{Project, Init, Url, Router};
use Yurly\Core\Utils\RegExp;
use Yurly\Core\Exception\ConfigException;

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
            new Project('www.testyurly.com', 'MultiSiteTests\\Site1', './tests', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testMultisiteSite1()
    {

        $this->expectOutputString('site1');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.site1.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/twigtest';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            new Project('www.site1.com', 'MultiSiteTests\\Site1', './tests', true),
            new Project('www.site2.com', 'MultiSiteTests\\Site2', './tests', true),
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
            new Project('www.site1.com', 'MultiSiteTests\\Site1', './tests', true),
            new Project('www.site2.com', 'MultiSiteTests\\Site2', './tests', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testProjectInitWithArray()
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
            'www.site1.com' => ['MultiSiteTests\\Site1', './tests'],
            'www.site2.com' => ['MultiSiteTests\\Site2', './tests'],
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testProjectInitWithRegexpHostMatchSite1()
    {

        $this->expectOutputString('{"result":"site1"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'prefix.site1.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            new Project(new RegExp('/^.+\.site1\.com$/'), 'MultiSiteTests\\Site1', './tests', true),
            new Project(new RegExp('/^.+\.site2\.com$/'), 'MultiSiteTests\\Site2', './tests', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testProjectInitWithRegexpHostMatchSite2()
    {

        $this->expectOutputString('{"result":"site2"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'prefix.site2.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            new Project(new RegExp('/^.+\.site1\.com$/'), 'MultiSiteTests\\Site1', './tests', true),
            new Project(new RegExp('/^.+\.site2\.com$/'), 'MultiSiteTests\\Site2', './tests', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testProjectInitWithArrayOfHosts()
    {

        $this->expectOutputString('{"result":"site1"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'prefix.site1.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            new Project(['www.site1.com', 'prefix.site1.com'], 'MultiSiteTests\\Site1', './tests', true),
        ];

        $app = new Init($projects);
        
        // Start 'em up
        $app->run();

    }

    public function testProjectInitWithNoMatch()
    {

        $this->expectException(ConfigException::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'www.site3.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['QUERY_STRING'] = '';

        $projects = [
            new Project('www.site1.com', 'MultiSiteTests\\Site1', './tests', true),
            new Project('www.site2.com', 'MultiSiteTests\\Site2', './tests', true),
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
