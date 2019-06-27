<?php declare(strict_types=1);

namespace Yurly\Core\Exception;

use Yurly\Core\Url;
use Yurly\Core\Project;

class RouteNotFoundException extends \Exception
{

    protected $url;
    protected $project;

    public function __construct(Url $url, Project $project, int $code = 0, \Exception $previous = null)
    {

        $this->url = $url;
        $this->project = $project;

        parent::__construct(sprintf('Could not find route for request uri %s.', $url->requestUri), $code, $previous);

    }

    /*
     * Return the URL that caused the error
     */
    public function getUrl()
    {

        return $this->url;

    }

    /*
     * Return the project
     */
    public function getProject()
    {

        return $this->project;

    }

}
