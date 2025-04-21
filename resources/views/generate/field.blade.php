<?php

namespace App\Fields;

use App\Services\FieldsService;

class DummyClass extends FieldsService
{
    /**
     * The unique identifier of the class.
     */
    const IDENTIFIER = 'DummyIdentifier';

    /**
     * Defines wether the class should be automatically loaded.
     */
    const AUTOLOAD = false;

    public function get()
    {
        return [];
    }
}