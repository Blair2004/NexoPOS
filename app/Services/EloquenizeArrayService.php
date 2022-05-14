<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class EloquenizeArrayService
{
    public function parse( Builder $query, $data )
    {
        foreach( $data as $fieldName => $arguments ) {
            match( $arguments[ 'comparison' ] ) {
                '<>'    =>  $query->where( $fieldName, '<>', $arguments[ 'value' ] ),
                '>'     =>  $query->where( $fieldName, '>', $arguments[ 'value' ] ),
                '<'     =>  $query->where( $fieldName, '<', $arguments[ 'value' ] ),
                default =>  $query->where( $fieldName, $arguments[ 'value' ] )
            };
        }
    }
}