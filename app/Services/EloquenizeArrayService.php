<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class EloquenizeArrayService
{
    public function parse( Builder $query, $data )
    {
        $query->where( function ( $query ) use ( $data ) {
            foreach ( $data as $fieldName => $arguments ) {
                match ( $arguments[ 'comparison' ] ) {
                    '<>' => $query->where( $fieldName, '<>', $arguments[ 'value' ] ),
                    '>' => $query->where( $fieldName, '>', $arguments[ 'value' ] ),
                    '<' => $query->where( $fieldName, '<', $arguments[ 'value' ] ),
                    '>=' => $query->where( $fieldName, '>=', $arguments[ 'value' ] ),
                    '<=' => $query->where( $fieldName, '<=', $arguments[ 'value' ] ),
                    'like' => $query->where( $fieldName, 'like', $arguments[ 'value' ] ),
                    'in' => $query->whereIn( $fieldName, (array) $arguments[ 'value' ] ),
                    'notIn' => $query->whereNotIn( $fieldName, (array) $arguments[ 'value' ] ),
                    default => $query->where( $fieldName, $arguments[ 'value' ] )
                };
            }
        } );
    }
}
