<?php

namespace App\Services;

class Module
{
    protected $module;

    protected $file;

    public function __construct( $file )
    {
        $this->file = $file;
    }
}
