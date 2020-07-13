<?php
namespace App\Services;

use App\Services\Helpers\ArrayHelper;
use App\Services\Helpers\App;
use App\Services\Helpers\Routes;
use App\Services\Helpers\Validation;
use App\Services\Helpers\Modules;

class Helper
{
    use ArrayHelper, 
        App,
        Routes,
        Modules,
        Validation;
}