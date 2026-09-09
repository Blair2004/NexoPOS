<?php

namespace Modules\NsOxen\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\NsOxen\Services\ToolRegistry;

/** @method static void registerTools(array $tools) */
class Oxen extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ToolRegistry::class;
    }
}
