<?php
namespace App\Services;

use App\Services\Helpers\App;

class CoreService
{
    public function installed()
    {
        return Helper::installed();
    }
}