<?php

namespace Tests\Models;

class DI
{

    public function __construct(Inject $context)
    {

        echo __CLASS__ . '::' . __FUNCTION__;

    }

}
