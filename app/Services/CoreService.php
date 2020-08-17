<?php
namespace App\Services;

use App\Services\Helpers\App;

class CoreService
{
    public function __construct()
    {
        $this->currency     =   app()->make( CurrencyService::class );
    }
    public function installed()
    {
        return Helper::installed();
    }
}