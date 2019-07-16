<?php declare(strict_types=1);

namespace Tests;

use Yurly\Core\Project;
use DI\Container;

class Config {

    public function __construct(Project $project)
    {

        $project->addContainer(new Container());

    }

}
