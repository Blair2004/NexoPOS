<?php
namespace App\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;

class EloquenizeArrayService
{
    public function parse( Builder $query, $data )
    {
        foreach( $data as $fieldName => $arguments ) {
            
        }
    }
}