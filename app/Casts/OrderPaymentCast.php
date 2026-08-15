<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model $model
     * @param  mixed $value
     * @return mixed
     */
    public function get( $model, string $key, $value, array $attributes )
    {
        return ns()->order->getPaymentLabel( $value );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model $model
     * @param  mixed $value
     * @return mixed
     */
    public function set( $model, string $key, $value, array $attributes )
    {
        return $value;
    }
}
