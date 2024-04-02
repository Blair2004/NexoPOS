<?php

namespace App\Casts;

use App\Models\Transaction;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TransactionOccurrenceCast implements CastsAttributes
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
            Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => __( 'Month Starts' ),
            Transaction::OCCURRENCE_MIDDLE_OF_MONTH => __( 'Month Middle' ),
            Transaction::OCCURRENCE_END_OF_MONTH => __( 'Month Ends' ),
            Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => __( 'X Days After Month Starts' ),
            Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS => __( 'X Days Before Month Ends' ),
            Transaction::OCCURRENCE_SPECIFIC_DAY => __( 'On Specific Day' ),
            default => __( 'Unknown Occurance' )
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
