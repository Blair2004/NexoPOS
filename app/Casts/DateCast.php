<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class DateCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string                              $key
     * @param  mixed                               $value
     * @param  array                               $attributes
     * @return mixed
     */
    public function get( $model, $key, $value, $attributes )
    {
        if ( $value !== null ) {
            return ns()->date->getFormatted( $value );
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string                              $key
     * @param  array                               $value
     * @param  array                               $attributes
     * @return mixed
     */
    public function set( $model, $key, $value, $attributes )
    {
        return $value;
    }
}
