<?php
namespace App\Contracts;

use Illuminate\Foundation\Events\Dispatchable;

abstract class NsDispatchable
{
    use Dispatchable;

    public function dispatch()
    {
        
    }
}