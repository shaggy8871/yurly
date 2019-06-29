<?php declare(strict_types=1);

namespace Yurly\Core\Exception;

use Yurly\Core\Project;

class ConfigException extends \Exception
{

    protected $project;

    public function __construct(?Project $project, string $message, int $code = 0, \Exception $previous = null)
    {

        $this->project = $project;

        parent::__construct($message, $code, $previous);

    }

    /*
     * Return the project
     */
    public function getProject()
    {

        return $this->project;

    }

};