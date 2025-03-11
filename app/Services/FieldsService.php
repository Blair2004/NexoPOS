<?php

namespace App\Services;

class FieldsService
{
    protected $fields = [];

    public function get()
    {
        return $this->fields;
    }

    public static function getIdentifier(): string
    {
        if ( isset( get_called_class()::$identifier ) ) {
            return get_called_class()::$identifier;
        }

        if ( get_called_class()::IDENTIFIER ) {
            return get_called_class()::IDENTIFIER;
        }
    }
}
