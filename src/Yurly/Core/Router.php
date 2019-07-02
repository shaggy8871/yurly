<?php declare(strict_types=1);

/**
 * This is where most of the magic happens
 */

namespace Yurly\Core;

use Yurly\Core\Exception\{
    ConfigException,
    RouteNotFoundException,
    ClassNotFoundException
};
use Yurly\Core\Interfaces\RouteResolverInterface;
use Yurly\Middleware\MiddlewareState;
use Yurly\Inject\Request\RequestInterface;

class Router
{

    const ROUTE_NOTFOUND = 'routeNotFound';
    const ROUTE_DEFAULT  = 'routeDefault';

    protected $project;
    protected $url;
    protected $caller;
    protected $log = [];

    public function __construct(Project $project)
    {

        $this->project = $project;

    }

    /**
     * Determine the target controller based on the url path
     */
    public function parseUrl(Url $url = null): bool
    {

        if ($url) {
            $this->url = $url; // override existing
        }

        if (!($this->url instanceof Url)) {
            throw new ConfigException($this->project, 'No Url instance supplied for parser.');
        }

        $pathComponents = $this->url->pathComponents;

        // Iterate through each and convert to class or method name
        foreach($pathComponents as &$pathComponent) {
            $pathComponent = str_replace('-', '_', ucfirst($pathComponent));
        }

        // Attempt 1: Look for RouteResolver type class in project and instantiate
        $method = 'resolve';
        $controller = $this->project->ns . '\\RouteResolver';
        if (class_exists($controller)) {

            $routeResolver = new $controller($this->project);

            if ($routeResolver instanceof RouteResolverInterface) {

                // Call the resolve() method
                $route = call_user_func([$routeResolver, $method], $this->project, $this->url);

                $this->log[] = sprintf("[parseUrl] Attempt 1: RouteResolver returned '%s'.", is_callable($route) ? '[Callable]' : $route);

                // If we get a string back in format $controller::$method, look for the method
                // If the return class method starts with "\" char, look outside the project controller tree
                if ((is_string($route)) && (strpos($route, '::') !== false)) {
                    list($controller, $method) = explode('::', ($route[0] != '\\' ? $this->project->ns . '\\Controllers\\' : '') . $route);
                    if ((class_exists($controller)) && (is_callable($controller . '::' . $method, true))) {
                        return $this->invokeClassMethod(new $controller($this->project), $method);
                    }
                }

                // Otherwise, if we get a closure back, call it
                if (is_callable($route)) {
                    if ((is_array($route)) && (count($route) == 2)) {
                        return $this->invokeClassMethod($route[0], $route[1]);
                    } else {
                        $reflection = new \ReflectionFunction($route);
                        if ($reflection->isClosure()) {
                            return $this->invokeFunction($route);
                        }
                    }
                }

            }

        }

        // Attempt 2: pointing to a specific route* method within the controller
        if (count($pathComponents) > 1) {
            $path = $pathComponents;
            $controllerClass = array_shift($path);
            $methodName = array_shift($path);
            $method = ($methodName != null ? 'route' . $methodName : self::ROUTE_DEFAULT);
            $controller = $this->findController($controllerClass);
            if ($controller) {
                $methodFound = $this->findMethod($controller, $method);
                if ($methodFound) {
                    $this->log[] = sprintf("[parseUrl] Attempt 2: Found method %s:%s.", $controller, $method);
                    return $methodFound;
                }
            }
        }

        // Attempt 3: check for a controller with routeDefault method
        if (count($pathComponents) == 1) {
            $path = $pathComponents;
            $lookupName = array_shift($path);
            $method = self::ROUTE_DEFAULT;
            $controller = $this->findController($lookupName);
            if ($controller) {
                $methodFound = $this->findMethod($controller, $method);
                if ($methodFound) {
                    $this->log[] = sprintf("[parseUrl] Attempt 3: Found method %s:%s.", $controller, $method);
                    return $methodFound;
                }
            }
        }

        // Attempt 4: look for a method in the Index controller
        $path = $pathComponents;
        $lookupName = array_shift($path);
        $method = ($lookupName ? 'route' . $lookupName : self::ROUTE_DEFAULT);
        $controller = $this->findController('Index');
        if ($controller) {
            $methodFound = $this->findMethod($controller, $method);
            if ($methodFound) {
                $this->log[] = sprintf("[parseUrl] Attempt 4: Found method %s:%s.", $controller, $method);
                return $methodFound;
            }
        }

        // Can't determine route, so start fallback steps
        return $this->routeNotFound();

    }

    /**
     * When a route cannot be determined, fall back in a controlled sequence
     */
    protected function routeNotFound(): bool
    {

        // @todo: Remove duplicate code above/below
        $pathComponents = $this->url->pathComponents;

        // Iterate through each and convert to class or method name
        foreach($pathComponents as &$pathComponent) {
            $pathComponent = str_replace('-', '_', ucfirst($pathComponent));
        }

        // Attempt 1: if we have a controller class, look for a routeNotFound method
        $path = $pathComponents;
        $method = self::ROUTE_NOTFOUND;
        $controller = $this->findController((empty($path) ? 'Index' : $path[0]));
        if (($controller) && (class_exists($controller)) && (is_callable($controller . '::' . $method))) {
            $routeNotFoundController = new $controller($this->project);
            $routeNotFoundController->$method($this->url, $this->project);
            $this->log[] = sprintf("[routeNotFound] Found method %s:%s.", $controller, $method);
            // Return true to indicate that a method is found
            return true;
        }

        // Finally, fail with an exception that can be trapped and handled
        throw new RouteNotFoundException($this->url, $this->project);

    }

    /**
     * Calls the specified class method and injects parameters
     * @param $class controller class object
     * @param $method string method name
     * @todo Instantiate parameters only once per global session
     */
    protected function invokeClassMethod($class, string $method): bool
    {

        if (!is_callable([$class, $method])) {
            return $this->routeNotFound();
        }

        $reflection = new \ReflectionMethod($class, $method);

        // Look up docblock annotations if available
        if ($reflection->getDocComment()) {
            $annotations = Utils\Annotations::parseDocBlock($reflection->getDocComment());
        } else {
            $annotations = null;
        }

        // Save caller information
        $this->caller = new Caller($class, $method, $annotations);

        // Call the method
        return $this->invokeCallable($reflection, $class);

    }

    /**
     * Calls the specified function or closure and injects parameters
     * @param $function the closure
     */
    protected function invokeFunction($function): bool
    {

        if (!is_callable($function)) {
            return $this->routeNotFound();
        }

        $reflection = new \ReflectionFunction($function);

        // Look up docblock annotations if available
        if ($reflection->getDocComment()) {
            $annotations = Utils\Annotations::parseDocBlock($reflection->getDocComment());
        } else {
            $annotations = null;
        }

        // Save caller information
        $this->caller = new Caller(null, $function, $annotations);

        // Call the function
        return $this->invokeCallable($reflection);

    }

    /**
     * Calls the method or closure and injects parameters dynamically
     */
    protected function invokeCallable($reflection, $class = null): bool
    {

        $this->beforeCall($reflection, $class);

        // Get an array of ReflectionParameter objects
        $params = $reflection->getParameters();
        // Injection array
        $inject = [];
        // Find first response class to set it as default
        $defaultResponseClass = null;
        // Loop through parameters to determine their class types
        foreach($params as $param) {
            try {
                $paramClass = $param->getClass();
            } catch (\Exception $e) {
                // Rethrow the error with further information
                throw new ClassNotFoundException($param->getName(), ($this->caller->controller ? get_class($this->caller->controller) : null), $this->caller->method);
            }
            // If it's not a class, inject a null value
            if (!($paramClass instanceof \ReflectionClass)) {
                $inject[] = null;
                continue;
            }
            // Special case for a Url, Project and Context type hints, send in the one we already have
            switch($paramClass->name) {
                case 'Yurly\\Core\\Project':
                    $inject[] = $this->project;
                    break;
                case 'Yurly\\Core\\Url':
                    $inject[] = $this->url;
                    break;
                case 'Yurly\\Core\\Context':
                    $inject[] = new Context($this->project, $this->url, $this->caller);
                    break;
                default:
                    if ($this->isRequestClass($paramClass->name, false)) {
                        $paramInstance = $this->instantiateRequestClass($param, $paramClass);
                    } else {
                        $paramInstance = new $paramClass->name(
                            new Context($this->project, $this->url, $this->caller)
                        );
                    }
                    // If this is a response class, set the default view filename
                    if ($this->isResponseClass($paramInstance)) {
                        if (($class) && (is_callable([$paramInstance, 'setViewFilename']))) {
                            $this->setResponseDefaults($paramInstance, $reflection, $class);
                        }
                        // Set the default response class if one isn't already set
                        if (!$defaultResponseClass) {
                            $defaultResponseClass = $paramInstance;
                        }
                    }
                    $inject[] = $paramInstance;
                    break;
            }
        }

        // Send the injected parameters into the identified method
        if ($reflection instanceof \ReflectionMethod) {
            $response = $reflection->invokeArgs($class, $inject);
        } else {
            $response = $reflection->invokeArgs($inject);
        }

        $this->afterCall($response, $class);

        if (($response !== false) && ($response !== null)) {
            // If object is a Response class, simply call the render method (assume it knows what to do)
            // Otherwise call the render method on the defined/default response class
            if ((is_object($response)) && ($this->isResponseClass($response))) {
                $response->render();
            } else {
                // If we have a default response class set, use it
                if ($defaultResponseClass) {
                    $responseClass = $defaultResponseClass;
                } else {
                    $responseClass = new \Yurly\Inject\Response\Html(
                        new Context($this->project, $this->url, $this->caller)
                    );
                    if (is_callable([$responseClass, 'setViewFilename'])) {
                        $this->setResponseDefaults($responseClass, $reflection, $class);
                    }
                }
                if (is_callable([$responseClass, 'render'])) {
                    $responseClass->render($response);
                }
            }
        }

        // Return true to indicate success
        return true;

    }

    /**
     * Allows a @before annotation to determine a different route
     */
    protected function beforeCall(&$reflection, &$class = null): void
    {

        $beforeHandlers = [];

        if ($class && method_exists($class, 'beforeAllRoutes')) {
            $beforeHandlers[] = 'beforeAllRoutes';
        }
        if (isset($this->caller->annotations['before'])) {
            $annotations = preg_split('/[ ,;]+/', $this->caller->annotations['before']);
            foreach($annotations as $annotation) {
                $beforeHandlers[] = $annotation;
            }
        }

        // Instantiate state handler
        $state = new MiddlewareState();

        foreach($beforeHandlers as $beforeHandler) {
            if (!$state->hasStopped()) {
                $this->invokeMiddleware($beforeHandler, $class, $state);
            }
        }

        $response = $state->getLastResponse();

        // If we get a string back in format $controller::$method, look for the method
        // If the return class method starts with "\" char, look outside the project controller tree
        if ((is_string($response)) && (strpos($response, '::') !== false)) {
            list($controller, $method) = explode('::', ($response[0] != '\\' ? $this->project->ns . '\\Controllers\\' : '') . $response);
            if ((class_exists($controller)) && (is_callable($controller . '::' . $method, true))) {
                // Override parameters:
                $class = new $controller($this->project);
                $reflection = new \ReflectionMethod($class, $method);
            }
        } else
        // If we get a method name back, look in the same class
        if ((is_string($response)) && (method_exists($class, $response))) {
            $reflection = new \ReflectionMethod($class, $response);
        } else
        // Otherwise, if we get a closure back, call it
        if (is_callable($response)) {
            if ((is_array($response)) && (count($response) == 2)) {
                // Override parameters:
                $class = new $response[0];
                $reflection = new \ReflectionMethod($response[0], $response[1]);
            } else {
                $reflection = new \ReflectionFunction($response);
            }
        }

    }

    /**
     * Allows an @after annotation to return a different response
     */
    protected function afterCall(&$response, &$class = null): void
    {

        $afterHandlers = [];

        if ($class && method_exists($class, 'afterAllRoutes')) {
            $afterHandlers[] = 'afterAllRoutes';
        }
        if (isset($this->caller->annotations['after'])) {
            $annotations = preg_split('/[ ,;]+/', $this->caller->annotations['after']);
            foreach($annotations as $annotation) {
                $afterHandlers[] = $annotation;
            }
        }

        // Instantiate state handler
        $state = new MiddlewareState();

        foreach($afterHandlers as $afterHandler) {
            if (!$state->hasStopped()) {
                $this->invokeMiddleware($afterHandler, $class, $state);
            }
        }

        $stateLastResponse = $state->getLastResponse();

        // We only overwrite if the response is non-null
        if ($stateLastResponse !== null) {
            $response = $stateLastResponse;
        }

    }

    /**
     * Calls each middleware handler method in turn, and updates $state where appropriate
     */
    protected function invokeMiddleware(string $handlerMethod, $class, MiddlewareState $state): void
    {

        $middlewareClass = null;
        $middlewareReflection = null;

        // If handler contains a string in format $controller::$method, look for the method
        // If the return class method starts with "\" char, look outside the project controller tree
        if ((is_string($handlerMethod)) && (strpos($handlerMethod, '::') !== false)) {
            list($controller, $method) = explode('::', ($handlerMethod[0] != '\\' ? $this->project->ns . '\\Controllers\\' : '') . $handlerMethod);
            if ((class_exists($controller)) && (is_callable($controller . '::' . $method, true))) {
                $middlewareClass = new $controller($this->project);
                $middlewareReflection = new \ReflectionMethod($middlewareClass, $method);
            }
        } else
        // If we get a method name back, look in the same class
        if ((is_string($handlerMethod)) && (method_exists($class, $handlerMethod))) {
            $middlewareClass = $class;
            $middlewareReflection = new \ReflectionMethod($class, $handlerMethod);
        } else
        // Otherwise if it's callable, it must be a function
        if (is_callable($handlerMethod)) {
            $middlewareClass = $class;
            $middlewareReflection = new \ReflectionFunction($handlerMethod);
        }

        if (!($middlewareReflection instanceof \ReflectionFunctionAbstract)) {
            $this->log[] = sprintf("Middleware handler '%s' not found, or is not a valid method.", $handlerMethod);
            return;
        }

        // Get an array of ReflectionParameter objects
        $params = $middlewareReflection->getParameters();
        // Injection array
        $inject = [];
        // Loop through parameters to determine their class types
        foreach($params as $param) {
            try {
                $paramClass = $param->getClass();
            } catch (\Exception $e) {
                // Rethrow the error with further information
                throw new ClassNotFoundException($param->getName(), ($this->caller->controller ? get_class($this->caller->controller) : null), $this->caller->method);
            }
            // If it's not a class, inject a null value
            if (!($paramClass instanceof \ReflectionClass)) {
                $inject[] = null;
                continue;
            }
            // Special case for known classes, send in the one we already have
            switch($paramClass->name) {
                case 'Yurly\\Core\\Project':
                    $inject[] = $this->project;
                    break;
                case 'Yurly\\Core\\Url':
                    $inject[] = $this->url;
                    break;
                case 'Yurly\\Core\\Context':
                    $inject[] = new Context($this->project, $this->url, $this->caller);
                    break;
                case 'Yurly\\Middleware\\MiddlewareState':
                    $inject[] = $state;
                    break;
                default:
                    $inject[] = null;
                    break;
            }
        }

        // Send the injected parameters into the identified method
        if ($middlewareReflection instanceof \ReflectionMethod) {
            $response = $middlewareReflection->invokeArgs($middlewareClass, $inject);
        } else {
            $response = $middlewareReflection->invokeArgs($inject);
        }

        // Overwrite last response if something new returned; the rest of $state should be preserved
        if (is_string($response) || is_callable($response)) {
            $state->setLastResponse($response);
        }

    }

    /**
     * Returns true if it's a Request class
     */
    protected function isRequestClass($class, bool $autoload = true): bool
    {

        return in_array('Yurly\\Inject\\Request\\RequestInterface', class_implements($class, $autoload));

    }

    /**
     * Returns true if it's a Response class
     */
    protected function isResponseClass($class, bool $autoload = true): bool
    {

        return in_array('Yurly\\Inject\\Response\\ResponseInterface', class_implements($class, $autoload));

    }

    /**
     * Special case for request parameters
     * If the parameter class contains a static createFromRequest method,
     * ask it to instantiate the class for us using the request data supplied.
     */
    protected function instantiateRequestClass(\ReflectionParameter $param, \ReflectionClass $paramClass): ?RequestInterface
    {

        try {

            $paramFactory = $paramClass->getMethod('createFromRequest');
            // Method exists, but is it static?
            if (!$paramFactory->isStatic()) {
                // Fall back
                return new $paramClass->name(
                    new Context($this->project, $this->url, $this->caller)
                );
            }

            $paramInstance = $paramFactory->invoke(null, new \Yurly\Inject\Request\Request(
                new Context($this->project, $this->url, $this->caller)
            ));

            // If we don't get an object back, set it to null for safety
            if (!is_object($paramInstance)) {
                $paramInstance = null;
            }

            // If the parameter doesn't allow null values, throw an error to prevent
            // the compiler from doing so
            if (($paramInstance == null) && (!$param->allowsNull())) {
                throw new ConfigException($this->project, sprintf("Method %s::createFromRequest returned null or a non-object, and Request parameter does not accept nulls.", $paramClass->name));
            }

            return $paramInstance;

        } catch (\ReflectionException $e) {
            // Didn't work so continue as normal
            return new $paramClass->name(
                new Context($this->project, $this->url, $this->caller)
            );
        }

    }

    /**
     * Copied and modified from http://php.net/manual/en/reflectionparameter.getclass.php#108620
     */
    protected function getParamClassName(\ReflectionParameter $param): ?string
    {

        preg_match('/\[\s\<\w+?>\s([\w\\\\]+)/s', $param->__toString(), $matches);
        return isset($matches[1]) ? $matches[1] : null;

    }

    /**
     * Inject details into the response class. Not available for closures.
     */
    protected function setResponseDefaults($responseClass, $reflection, $controllerClass = null): void
    {

        if (!is_callable([$responseClass, 'setViewFilename'])) {
            return;
        }
        // Not available if it's a closure since we have no context
        if ($reflection->isClosure()) {
            return;
        }

        // Try to auto-detect details using controller
        if ($controllerClass) {
            // Reflect on the controllerClass
            $controllerClassReflection = new \ReflectionClass($controllerClass);
            $controllerPath = pathinfo($controllerClassReflection->getFileName());
            // Inject view filename
            $methodName = $reflection->getName();
            if (substr($methodName, 0, strlen('route')) == 'route') {
                $responseClass->setViewFilename($controllerPath['filename'] . '/' . strtolower(str_replace('route', '', $methodName)));
            }
        }

    }

    /**
     * Find a controller that matches the name specified
     */
    protected function findController($controller): ?string
    {

        if (!$controller) {
            return null;
        }

        $projectControllers = $this->project->ns . '\\Controllers\\';

        if (class_exists($projectControllers . $controller)) {
            return $projectControllers . $controller;
        }

        return null;

    }

    /**
     * Attempt to find the appropriate method to call
     */
    protected function findMethod($controller, string $method): bool
    {

        if (method_exists($controller, $method)) {
            return $this->invokeClassMethod(new $controller($this->project), $method);
        }

        $methodMatch = $this->scanForMethodMatches($controller);
        if ($methodMatch) {
            return $this->invokeClassMethod(new $controller($this->project), $methodMatch);
        }

        return false;

    }

    /**
     * Find a matching method using annotation matching
     */
    protected function scanForMethodMatches($controller): ?string
    {

        $controllerClassReflection = new \ReflectionClass($controller);
        $methods = $controllerClassReflection->getMethods();
        foreach($methods as $method) {
            if ($method->getDocComment()) {
                $annotation = Utils\Annotations::parseDocBlock($method->getDocComment());
                if (isset($annotation['canonical'])) {
                    $canonical = Utils\Canonical::templateToRegex($annotation['canonical']);
                    // Return as soon as a match is found
                    if (preg_match($canonical, $this->url->getRequestUri())) {
                        return $method->getName();
                    }
                }
            }
        }

        return null;

    }

    /**
     * Return the project
     */
    public function getProject(): Project
    {

        return $this->project;

    }

    /**
     * Return the parsed url class that we're using
     */
    public function getUrl(): Url
    {

        return $this->url;

    }

    /**
     * Return the caller information
     */
    public function getCaller(): Caller
    {

        return $this->caller;

    }

    /**
     * Return the route log for debugging purposes
     */
    public function getLog(): array
    {

        return $this->log;

    }

}
