<?php

namespace App\Services;

class FieldsService
{
    protected $identifier;

    protected $fields = [];

    public function get()
    {
        return $this->fields;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
