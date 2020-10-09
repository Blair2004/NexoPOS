<?php
namespace App\Services;

class FieldsService 
{
    protected $fields   =   [];
    
    public function get()
    {
        return $this->fields;
    }
}