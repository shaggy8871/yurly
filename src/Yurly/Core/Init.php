<?php declare(strict_types=1);

namespace Yurly\Core;

use Yurly\Inject\Response\Phtml;
use Yurly\Core\Utils\RegExp;
use Yurly\Core\Exception\{
    ConfigException,
    UnknownPropertyException,
    RouteNotFoundException
};

class Init
{

    protected $projects;
    protected $onRouteNotFound;
    protected $onUrlParseError;
    protected $router;

    public function __construct(array $projects = [])
    {

        // Save projects
        $this->projects = $projects;

    }

    /**
     * Call a function if we are unable to find a route
     */
    public function onRouteNotFound(callable $callback): void
    {

        $this->onRouteNotFound = $callback;

    }

    /**
     * Call a function if we are unable to parse a URL
     */
    public function onUrlParseError(callable $callback): void
    {

        $this->onUrlParseError = $callback;

    }

    /**
     * Run the project
     */
    public function run(): void
    {

        // Initialize router
        try {

            $url = UrlFactory::autodetect();
            $this->router = new Router($this->getProjectFromUrl($url));
            $this->router->parseUrl($url);

        } catch (ConfigException $e) {

            if ($e->getProject() instanceof Project) {

                if ($e->getProject()->debugMode) {
                    // Display a default error page
                    $response = new Phtml($context);
                    $response
                        ->setStatusCode(404)
                        ->setViewDir(__DIR__ . '/Scripts')
                        ->setViewFilename('error.phtml')
                        ->setViewParams([
                            'statusCode' => 404,
                            'exceptionMessage' => $e->getMessage()
                        ])
                        ->render();
                } else {
                    // ignore
                }

            } else {
                // Re-throw exception
                throw $e;
            }

        } catch (RouteNotFoundException $e) {

            $context = new Context($e->getProject(), $e->getUrl());

            if ($this->onRouteNotFound) {
                // Call the user defined route not found handler
                call_user_func($this->onRouteNotFound, [
                    'statusCode' => 404,
                    'context' => $context,
                    'exceptionMessage' => $e->getMessage()
                ]);
            } else
            if ($e->getProject()->debugMode) {
                // Display a default error page
                $response = new Phtml($context);
                $response
                    ->setStatusCode(404)
                    ->setViewDir(__DIR__ . '/Scripts')
                    ->setViewFilename('error.phtml')
                    ->setViewParams([
                        'statusCode' => 404,
                        'exceptionMessage' => $e->getMessage()
                    ])
                    ->render();
            } else {
                // ignore
            }

        }

    }

    /**
     * Returns the router log data
     */
    public function getRouterLog(): array
    {

        return $this->router->getLog();

    }

    /**
     * Allow read access only
     */
    public function __get(string $property)
    {

        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new UnknownPropertyException($property, __CLASS__);
        }

    }

    /**
    * Determine the project folder from the url hostname
    */
    private function getProjectFromUrl(Url $url): Project
    {

        foreach($this->projects as $k => $project) {

            if ($project instanceof Project && $this->urlMatchesProject($url, $project)) {
                return $project;
            } else {
                if (is_string($k) && is_array($project)) {
                    $project = array_merge($project, array_fill(0, 2, false));
                    list($ns, $path, $debugMode) = $project;
                    if (!$ns) {
                        throw new ConfigException(null, "Project configuration must have a namespace assigned");
                    }
                    return new Project($k, $ns, $path, $debugMode);
                }
            }

        }

        throw new ConfigException(null, sprintf('Cannot determine project path from host %s', $url->host));

    }

    /**
     * Returns true if the URL matches this project
     */
    private function urlMatchesProject(Url $url, Project $project): bool
    {

        $hosts = $project->getHosts();

        if (is_string($hosts) && $url->host == $hosts) {
            return true;
        } else {
            if (is_array($hosts) && in_array($url->host, $hosts)) {
                return true;
            } else {
                if ($hosts instanceof RegExp && $hosts->matches($url->host)) {
                    return true;
                }
            }
        }

        return false;

    }

}
