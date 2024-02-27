<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class GenderCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  mixed                               $value
     * @return mixed
     */
    public function get( $model, string $key, $value, array $attributes )
    {
        return match ( $value ) {
            'male' => __( 'Male' ),
            'female' => __( 'Female' ),
            default => __( 'Not Defined' ),
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  mixed                               $value
     * @return mixed
     */
    public function set( $model, string $key, $value, array $attributes )
    {
        return $value;
    }
}
