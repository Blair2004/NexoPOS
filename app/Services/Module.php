<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;

class Module
{
    protected $module;

    protected $file;

    public function __construct( $file )
    {
        $this->file = $file;
    }
}
