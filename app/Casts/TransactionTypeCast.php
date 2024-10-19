<?php

namespace App\Casts;

use App\Models\Transaction;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TransactionTypeCast implements CastsAttributes
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
            Transaction::TYPE_DIRECT => __( 'Direct Transaction' ),
            Transaction::TYPE_INDIRECT => __( 'Indirect Transaction' ),
            Transaction::TYPE_RECURRING => __( 'Recurring Transaction' ),
            Transaction::TYPE_ENTITY => __( 'Entity Transaction' ),
            Transaction::TYPE_SCHEDULED => __( 'Scheduled Transaction' ),
            default => sprintf( __( 'Unknown Type (%s)' ), $value ),
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
