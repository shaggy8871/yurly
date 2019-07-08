<?php

namespace Tests\Models;

use Yurly\Inject\Request\Request;

class InstantiateRequest extends Request
{

    public static function createFromRequest(Request $request): self
    {

        echo "TestsModelsInstantiateRequest";

        return new static($request->getContext());

    }

}
