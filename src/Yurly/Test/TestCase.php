<?php declare(strict_types=1);

namespace Yurly\Test;

use Yurly\Core\{Project, Url, Caller, Context, Router};
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
    protected $controller;
    protected $method;
    protected $caller;
    protected $context;
    protected $router;
    protected $requestClass;
    protected $responseClass;

    public function setProject(Project $project): self
    {

        $this->project = $project;

        return $this;

    }

    public function setProjectHost(string $projectHost): self
    {

        $this->projectHost = $projectHost;

        return $this;

    }

    public function setProjectNamespace(string $projectNamespace): self
    {

        $this->projectNamespace = $projectNamespace;

        return $this;

    }

    public function setProjectPath(string $projectPath): self
    {

        $this->projectPath = $projectPath;

        return $this;

    }

    public function setProjectDebugMode(bool $projectDebugMode): self
    {

        $this->projectDebugMode = $projectDebugMode;

        return $this;

    }

    public function setUrl(string $url): self
    {

        $parsedUrl = parse_url($url);

        $this->url = new Url([
            'host' => $parsedUrl['host'] ?? static::DEFAULT_HOST,
            'pathComponents' => explode('/', substr($parsedUrl['path'], 1)),
            'requestUri' => $parsedUrl['path'],
        ]);

        return $this;

    }

    public function setController(string $controller): self
    {

        $this->controller = $controller;

    }

    public function setControllerMethod(string $controllerMethod): self
    {

        list($this->controller, $this->method) = explode('::', $controllerMethod);

    }

    public function setCaller(Caller $caller): self
    {

        $this->caller = $caller;

        return $this;

    }

    public function setContext(Context $context): self
    {

        $this->context = $context;

        return $this;

    }

    public function withRequestClass(string $requestClass): self
    {

        $this->requestClass = $requestClass;

        return $this;

    }

    public function withResponseClass(string $responseClass): self
    {

        $this->responseClass = $responseClass;

        return $this;

    }

    public function skipBefore() {}

    public function skipAfter() {}

    public function skipMiddleware() {}

    public function skipInvoke(): self
    {

        $this->router = $this->getMockBuilder(Router::class)
            ->setConstructorArgs([$this->getProject()])
            ->setMethods(['invokeCallable'])
            ->getMock();
        $this->router
            ->expects($this->once())
            ->method('invokeCallable')
            ->will($this->returnCallback(function($reflection, $class) { return true; }));

        return $this;

    }

    public function getCaller(): Caller
    {

        $this->skipInvoke();

        $this->router->parseUrl($this->getUrl());

        return $this->router->getCaller();

    }

    /**
     * Parses the URL but doesn't actually call the target route method, but instead returns the reflection
     */
    public function getRoute(): string
    {

        $caller = $this->getCaller();

        return get_class($caller->getController()) . '::' . $caller->getMethod();

    }

    public function route()
    {

        $this->getProject();
        $this->getUrl();

        if (!$this->controller && !$this->method) {
            /**
             * 1. Create mock
             * 2. Enable all methods except invokeCallable()
             * 3. Trap parameters $reflection and $class
             * 4. Determine controller and method
             */
        }
        if (!$this->controller) {
            $this->controller = 'Index';
        }
        if (!$this->method) {
            $this->method = 'routeDefault';
        }
        if (!$this->caller) {
            $this->caller = new Caller();
        }
        if (!$this->context) {
            $this->context = new Context($this->project, $this->url, $this->caller);
        }

    }

    protected function getComposerDefaults()
    {

        $composer = json_decode(file_get_contents('composer.json'), true);
        if (isset($composer['autoload']['psr-4'])) {
            $autoloadClasses = $composer['autoload']['psr-4'];
        }

        return is_array($autoloadClasses) && count($autoloadClasses) > 0 ? array_splice($autoloadClasses, 0, 1) : [];

    }

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

    protected function getUrl(): Url
    {

        if (!$this->url) {
            $this->setUrl('http://' . static::DEFAULT_HOST . '/');
        }

        return $this->url;

    }

}