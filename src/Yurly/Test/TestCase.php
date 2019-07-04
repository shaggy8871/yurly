<?php declare(strict_types=1);

namespace Yurly\Test;

use Yurly\Core\{Project, Url, Caller, Context, Router};
use Yurly\Inject\Response\ResponseInterface;
use Yurly\Inject\Request\RequestInterface;
use PHPUnit\Framework\TestCase AS PhpUnitTestCase;

class TestCase extends PhpUnitTestCase
{

    const DEFAULT_HOST = 'www.yurlytest.com';

    protected $project;
    protected $projectHost;
    protected $projectNamespace;
    protected $projectPath;
    protected $projectDebugMode;
    protected $url;
    protected $caller;
    protected $context;
    protected $routerMock;

    /**
     * Set a project class
     */
    public function setProject(Project $project): self
    {

        $this->project = $project;

        return $this;

    }

    /**
     * Set a project flag
     */
    public function setProjectHost(string $projectHost): self
    {

        $this->projectHost = $projectHost;

        return $this;

    }

    /**
     * Set a project flag
     */
    public function setProjectNamespace(string $projectNamespace): self
    {

        $this->projectNamespace = $projectNamespace;

        return $this;

    }

    /**
     * Set a project flag
     */
    public function setProjectPath(string $projectPath): self
    {

        $this->projectPath = $projectPath;

        return $this;

    }

    /**
     * Set a project flag
     */
    public function setProjectDebugMode(bool $projectDebugMode): self
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
     */
    public function setUrl(string $url): self
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
     * Get or set the URL
     * 
     * @dependentMethod     setUrl()
     */
    protected function getUrl(): Url
    {

        if (!$this->url) {
            $this->setUrl('http://' . static::DEFAULT_HOST . '/');
        }

        return $this->url;

    }

    /**
     * Set the caller - if not specified, will try to determine using the URL
     * 
     * @dependentMethod     getUrl()
     */
    public function setCaller(?Caller $caller = null): self
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
    public function getCaller(): Caller
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
    public function setContext(?Project $project = null, ?Url $url = null, ?Caller $caller = null): self
    {

        $this->context = new Context($project ?? $this->getProject(), $url ?? $this->getUrl(), $caller ?? $this->getCaller());

        return $this;

    }

    /**
     * Get or set the context
     * 
     * @dependentMethod     setContext()
     */
    public function getContext(): Context
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
    public function getRoute(): string
    {

        $caller = $this->getCaller();

        return ($caller->getController() ? get_class($caller->getController()) . '::' : '') . $caller->getMethod();

    }

    /**
     * Create a Request class mock and enable the $callback to take its place
     * 
     * @var $class          The request class
     * @var $callback       A closure to replace the mocked functionality, accepts ($self) as parameter
     * 
     * @dependentMethod     getContext()
     */
    public function getRequestMock(string $class, callable $callback): RequestInterface
    {

        $requestMock = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->getContext()])
            ->setMethods(['hydrate'])
            ->getMock();

        $requestMock
            ->expects($this->once())
            ->method('hydrate')
            ->will($this->returnCallback(function() use ($callback, $requestMock) { $callback($requestMock); }));

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
    public function getResponseMock(string $class, callable $callback): ResponseInterface
    {

        $responseMock = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->getContext()])
            ->setMethods(['render'])
            ->getMock();

        $responseMock
            ->expects($this->once())
            ->method('render')
            ->will($this->returnCallback(function($params) use ($callback) { $callback($params); }));

        return $responseMock;

    }

    /**
     * Calls the specified route with the supplied mock parameters
     * 
     * @var $url            A full or relative URL
     * @var $mockParams     An array of request/response classes to mock
     * 
     * @dependentMethod     setUrl(), getUrl(), getRouterMock()
     */
    public function callRouteWithMocks(string $url, array $mockParams = []): void
    {

        $this->setUrl($url);

        $routerMock = $this->getRouterMock();

        if (!empty($mockParams)) {
            $routerMock->setMockParameters($mockParams);
        }

        $routerMock->parseUrl($this->getUrl($url));
 
    }

    /**
     * Calls the route via URL and outputs the result as per normal
     * 
     * @var $url            A full or relative URL
     * 
     * @dependentMethod     callRouteWithMocks()
     */
    public function callRoute(string $url): void
    {

        $this->callRouteWithMocks($url);

    }

    /**
     * Calls the route using the urlFor lookup method
     * 
     * @var $callback       Expects a callback, callable, controllerMethod (Index::routeDefault) or method name
     * @var $params         Optional URL parameters to replace
     * @var $caller         Optional context if URL is relative to current caller
     * 
     * @dependentMethod     getRouterMock(), callRoute()
     */
    public function callRouteFor($callback, ?array $params = [], ?Caller $caller = null)
    {

        $urlFor = $this->getRouterMock()->urlFor($callback, $params, $caller);

        return $this->callRoute($urlFor);

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
     * Get default namespace and path settings for projects
     */
    protected function getComposerDefaults()
    {

        $composer = json_decode(file_get_contents('composer.json'), true);
        if (isset($composer['autoload']['psr-4'])) {
            $autoloadClasses = $composer['autoload']['psr-4'];
        }

        return is_array($autoloadClasses) && count($autoloadClasses) > 0 ? array_splice($autoloadClasses, 0, 1) : [];

    }

}