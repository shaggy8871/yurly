<?php declare(strict_types=1);

namespace Yurly\Core;

use Yurly\Core\Exception\{
    ConfigException,
    UnknownPropertyException,
    RouteNotFoundException
};
use Yurly\Inject\Response\Phtml;

class Init
{

    protected $projects;
    protected $onRouteNotFound;

    public function __construct(array $projects = [])
    {

        // Save projects
        $this->projects = $projects;

    }

    /*
     * Call a function if we are unable to route
     */
    public function onRouteNotFound(callable $callback): void
    {

        $this->onRouteNotFound = $callback;

    }

    /*
     * Run the project
     */
    public function run(): void
    {

        // Initialize router
        try {

            $url = UrlFactory::autodetect();
            $router = new Router($this->getProjectFromUrl($url));
            $router->parseUrl($url);

        } catch (RouteNotFoundException $e) {

            $context = new Context($e->getProject(), $e->getUrl());

            if ($this->onRouteNotFound) {
                // Call the user defined route not found handler
                call_user_func($this->onRouteNotFound, [
                    'statusCode' => 404,
                    'context' => $context,
                    'exceptionMessage' => $e->getMessage()
                ]);
            } else {
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
            }

        }

    }

    /*
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

    /*
    * Determine the project folder from the url hostname
    */
    private function getProjectFromUrl(Url $url): Project
    {

        if (array_key_exists($url->host, $this->projects)) {
            return $this->createProject($this->projects[$url->host]);
        } else {
            throw new ConfigException('Cannot determine project path from host ' . $url->host);
        }

    }

    /*
    * Creates a project object
    */
    private function createProject($project): Project
    {

        if (is_a($project, 'Yurly\\Core\\Project')) {
            return $project;
        } else
        if (is_array($project)) {
            $project = array_merge($project, array_fill(0, 2, false));
            list($ns, $path, $debugMode) = $project;
            if (!$ns) {
                throw new ConfigException("Project configuration must have a namespace assigned");
            }
        } else {
            list($ns, $path, $debugMode) = array($project, '', false);
        }

        return new Project($ns, $path, $debugMode);

    }

}
