<?php declare(strict_types=1);

namespace Yurly\Core;

abstract class Controller
{

    protected $project;

    public function __construct(Project $project)
    {

        $this->project = $project;

    }

}
