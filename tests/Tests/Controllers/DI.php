<?php declare(strict_types=1);

namespace Tests\Controllers;

use Yurly\Core\{Controller, Project};
use Tests\Models\DI as DIModel;

class DI extends Controller
{

    public function __construct(Project $project, DIModel $model)
    {
        
    }

    public function routeDefault(DIModel $model): string
    {

        return __CLASS__ . '::' . __FUNCTION__;

    }

}
