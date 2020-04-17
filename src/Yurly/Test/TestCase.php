<?php declare(strict_types=1);

namespace Yurly\Test;

use Yurly\Core\{Project, Url, Caller, Context, Router};
use Yurly\Inject\Response\ResponseInterface;
use Yurly\Inject\Request\RequestInterface;
use PHPUnit\Framework\TestCase AS PhpUnitTestCase;

class TestCase extends PhpUnitTestCase
{

    const DEFAULT_HOST = 'www.yurlytest.com';

    private $project;
    private $projectHost;
    private $projectNamespace;
    private $projectPath;
    private $projectDebugMode;
    private $url;
    private $caller;
    private $context;
    private $routerMock;

    /**
     * Set a project class
     */
    protected function setProject(Project $project): self
    {

        $this->project = $project;

        return $this;

    }

    /**
     * Set a project flag
     */
    protected function setProjectHost(string $projectHost): self
    {

        $this->projectHost = $projectHost;

        return $this;

    }

    /**
     * Set a project flag
     */
    protected function setProjectNamespace(string $projectNamespace): self
    {

        $this->projectNamespace = $projectNamespace;

        return $this;

    }

    /**
     * Set a project flag
     */
    protected function setProjectPath(string $projectPath): self
    {

        $this->projectPath = $projectPath;

        return $this;

    }

    /**
     * Set a project flag
     */
    protected function setProjectDebugMode(bool $projectDebugMode): self
    {

        $this->projectDebugMode = $projectDebugMode;

        return $this;

    }

    /**
     * Get or set the project with defaults
     * 
     * @dependentMethod     getComposerDefaults()
     */
    protected function getProject(): Project
    {

        if (!$this->project) {

            $composerDefaults = $this->getComposerDefaults();
            $defaultNamespace = rtrim(key($composerDefaults), '\\');
            $defaultPath = rtrim(current($composerDefaults), '/');
    
            $this->projectHost = $this->projectHost ?? static::DEFAULT_HOST;
            $this->projectNamespace = $this->projectNamespace ?? $defaultNamespace;
            $this->projectPath = $this->projectPath ?? $defaultPath;
            $this->projectDebugMode = $this->setProjectDebugMode ?? false;

            $this->project = new Project($this->projectHost, $this->projectNamespace, $this->projectPath, $this->projectDebugMode);

        }

        return $this->project;

    }

    /**
     * Set the URL
     * 
     * @var $url            The URL to use when finding the route
     */
    protected function setUrl(string $url): self
    {

        $parsedUrl = parse_url($url);

        $this->url = new Url([
            'host' => $parsedUrl['host'] ?? static::DEFAULT_HOST,
            'pathComponents' => explode('/', substr($parsedUrl['path'], 1) ?: ''),
            'requestUri' => $parsedUrl['path'],
        ]);

        return $this;

    }

    /**
     * Sets the URL using a callback lookup
     * 
     * @dependentMethod     setUrl(), getRouterMock()
     */
    protected function setUrlFor($callback, ?array $params = [], ?Caller $caller = null): self
    {

        return $this->setUrl($this->getRouterMock()->urlFor($callback, $params, $caller));

    }

    /**
     * Get or set the URL
     */
    protected function getUrl(): Url
    {

        if (!($this->url instanceof Url)) {
            throw new \Exception('URL is not set. Call the $this->setUrl() method first.');
        }

        return $this->url;

    }

    /**
     * Set the caller - if not specified, will try to determine using the URL
     * 
     * @dependentMethod     getUrl()
     */
    protected function setCaller(?Caller $caller = null): self
    {

        if ($caller instanceof Caller) {
            $this->caller = $caller;
        } else {
            $routerMock = $this->getRouterMock(['invokeCallable']);
            $routerMock->parseUrl($this->getUrl());

            $this->caller = $routerMock->getCaller();
        }

        return $this;

    }

    /**
     * Get or set the caller
     * 
     * @dependentMethod     setCaller()
     */
    protected function getCaller(): Caller
    {

        if (!$this->caller) {
            $this->setCaller();
        }

        return $this->caller;

    }

    /**
     * Set the context
     * 
     * @dependentMethod     getProject(), getUrl(), getCaller()
     */
    protected function setContext(?Project $project = null, ?Url $url = null, ?Caller $caller = null): self
    {

        $this->context = new Context($project ?? $this->getProject(), $url ?? $this->getUrl(), $caller ?? $this->getCaller());

        return $this;

    }

    /**
     * Get or set the context
     * 
     * @dependentMethod     setContext()
     */
    protected function getContext(): Context
    {

        if (!$this->context) {
            $this->setContext();
        }

        return $this->context;

    }

    /**
     * Parses the URL but doesn't actually call the target route method; instead it returns the controller and method name
     * 
     * @dependentMethod     getCaller()
     */
    protected function getRoute(): string
    {

        $caller = $this->getCaller();

        return ($caller->getController() ? get_class($caller->getController()) . '::' : '') . $caller->getMethod();

    }

    /**
     * Calls the route twice, first to get the caller, and then to send mocks
     * Returns the raw route response, typically an array, rather than the resulting
     * string output.
     * 
     * @var $mockParams     Optional; an array of request/response classes to mock
     * 
     * @dependentMethod     setCaller(), getCaller(), callRouteWithMocks()
     */
    protected function getRouteResponse(array $mockParams = [])
    {

        $responseResult = null;

        // Make sure the caller is set
        $this->setCaller();

        $caller = $this->getCaller();

        $reflection = new \ReflectionMethod($caller->getController(), $caller->getMethod());

        foreach($reflection->getParameters() as $param) {
            try {
                $paramClass = $param->getClass();
                if ($paramClass->isSubclassOf('Yurly\\Inject\\Response\\ResponseInterface')) {
                    if (isset($mockParams[$paramClass->name])) {
                        throw new \Exception(sprintf("The getRouteResponse() method injects a mock response class by default."));
                    }
                    $mockParams[$paramClass->name] = $this->getResponseMock($paramClass->name, function($params) use (&$responseResult) { $responseResult = $params; });
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        // No response method found? Throw an exception to notify
        if (empty($mockParams)) {
            throw new \Exception(sprintf("No Response class found in '%s'.", ($caller->getController() ? get_class($caller->getController()) . '::' : '') . $caller->getMethod()));
        }

        $this->callRouteWithMocks($mockParams);

        return $responseResult;

    }

    /**
     * Calls the specified route with the supplied mock parameters
     * 
     * @var $mockParams     Optional; an array of request/response classes to mock
     * 
     * @dependentMethod     getUrl(), getRouterMock()
     */
    protected function callRouteWithMocks(array $mockParams = []): void
    {

        $routerMock = $this->getRouterMock();

        if (!empty($mockParams)) {
            $routerMock->setMockParameters($mockParams);
        }

        $routerMock->parseUrl($this->getUrl());
 
    }

    /**
     * Calls the route via URL and outputs the result as per normal
     * 
     * @dependentMethod     callRouteWithMocks()
     */
    protected function callRoute(): void
    {

        $this->callRouteWithMocks();

    }

    /**
     * Get a mocked Router class with one or more methods replaced
     * 
     * @var $mockMethods    An array of methods to mock [invokeCallable, beforeCall, afterCall]
     * 
     * @dependentMethod     getProject()
     */
    protected function getRouterMock(?array $mockMethods = null)
    {

        $this->routerMock = $this->getMockBuilder(Router::class)
            ->setConstructorArgs([$this->getProject()])
            ->setMethods($mockMethods)
            ->getMock();

        if (!$mockMethods) {
            return $this->routerMock;
        }

        foreach($mockMethods as $method) {
            switch($method) {
                case 'invokeCallable':
                    $this->routerMock
                        ->expects($this->once())
                        ->method($method)
                        ->will($this->returnCallback(function($reflection, $class) { return true; }));
                    break;
                case 'beforeCall':
                case 'afterCall':
                    $this->routerMock
                        ->expects($this->once())
                        ->method($method)
                        ->will($this->returnCallback(function($reflection, $class) { }));
                    break;
            }
        }

        return $this->routerMock;

    }

    /**
     * Get the router mock class's log file
     */
    protected function getRouterMockLog()
    {

        return $this->routerMock->getLog();

    }

    /**
     * Create a Request class mock and enable the $callback to take its place
     * 
     * @var $class          The request class
     * @var $callback       A closure to replace the mocked functionality, accepts ($self) as parameter
     * 
     * @dependentMethod     getContext()
     */
    protected function getRequestMock(string $class, ?callable $callback = null): RequestInterface
    {

        $requestMock = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->getContext()])
            ->setMethods(['hydrate'])
            ->getMock();

        $requestMock
            ->method('hydrate')
            ->will($this->returnCallback(function() use ($callback, $requestMock) {
                if (is_callable($callback)) {
                    $callback($requestMock);
                }
            }));

        return $requestMock;

    }

    /**
     * Create a Response class mock and enable the $callback to take its place
     * 
     * @var $class          The response class
     * @var $callback       A closure to replace the mocked functionality, accepts ($params) as parameter
     * 
     * @dependentMethod     getContext()
     */
    protected function getResponseMock(string $class, ?callable $callback = null): ResponseInterface
    {

        $responseMock = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->getContext()])
            ->setMethods(['render', 'redirect', 'assertRedirect', 'assertStatusCode', 'assertOk', 'assertNotFound', 'assertContentType'])
            ->getMock();

        $responseMock
            ->method('render')
            ->will($this->returnCallback(function($params) use ($callback) {
                if (is_callable($callback)) {
                    $callback($params);
                }
            }));
        $responseMock
            ->method('redirect')
            ->will($this->returnCallback(function(string $url, int $statusCode = 302) use ($responseMock) {
                $responseMock->setStatusCode($statusCode);
                $responseMock->redirect = (object) [
                    'url' => $url,
                    'statusCode' => $statusCode
                ];
            }));
        // Helper assertions
        $responseMock
            ->method('assertRedirect')
            ->will($this->returnCallback(function(string $url) use ($responseMock) {
                $this->assertEquals($responseMock->redirect->url, $url);
            }));
        $responseMock
            ->method('assertStatusCode')
            ->will($this->returnCallback(function(int $statusCode) use ($responseMock) {
                $this->assertEquals($responseMock->statusCode, $statusCode);
            }));
        $responseMock
            ->method('assertOk')
            ->will($this->returnCallback(function() use ($responseMock) {
                $this->assertEquals($responseMock->statusCode, 200);
            }));
        $responseMock
            ->method('assertNotFound')
            ->will($this->returnCallback(function() use ($responseMock) {
                $this->assertEquals($responseMock->statusCode, 404);
            }));
        $responseMock
            ->method('assertContentType')
            ->will($this->returnCallback(function(string $contentType) use ($responseMock) {
                $this->assertEquals($responseMock->contentType, $contentType);
            }));

        return $responseMock;

    }

    /**
     * Resets all saved private values
     */
    protected function reset(): void
    {

        $this->project = null;
        $this->projectHost = null;
        $this->projectNamespace = null;
        $this->projectPath = null;
        $this->projectDebugMode = null;
        $this->url = null;
        $this->caller = null;
        $this->context = null;
        $this->routerMock = null;

    }

    /**
     * Get default namespace and path settings for projects
     */
    private function getComposerDefaults()
    {

        $composer = json_decode(file_get_contents('composer.json'), true);
        if (isset($composer['autoload']['psr-4'])) {
            $autoloadClasses = $composer['autoload']['psr-4'];
        }

        return is_array($autoloadClasses) && count($autoloadClasses) > 0 ? array_splice($autoloadClasses, 0, 1) : [];

    }

}