<?php

namespace App\Fields;

use App\Services\FieldsService;

class DummyClass extends FieldsService
{
    const IDENTIFIER = 'DummyIdentifier';
    const AUTOLOAD = false;

    public function get()
    {
        return [];
    }
}