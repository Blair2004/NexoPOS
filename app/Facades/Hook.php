<?php

namespace App\Facades;

use App\Classes\Hook as ClassesHook;
use Illuminate\Support\Facades\Facade;

class Hook extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ClassesHook::class;
    }
}
