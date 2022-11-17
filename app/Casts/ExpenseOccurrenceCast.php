<?php

namespace App\Casts;

use App\Models\Expense;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ExpenseOccurrenceCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return match( $value ) {
            Expense::OCCURRENCE_X_AFTER_MONTH_STARTS => __( 'Month Starts' ),
            Expense::OCCURRENCE_MIDDLE_OF_MONTH => __( 'Month Middle' ),
            Expense::OCCURRENCE_END_OF_MONTH => __( 'Month Ends' ),
            Expense::OCCURRENCE_X_AFTER_MONTH_STARTS => __( 'X Days After Month Starts' ),
            Expense::OCCURRENCE_X_BEFORE_MONTH_ENDS => __( 'X Days Before Month Ends' ),
            Expense::OCCURRENCE_SPECIFIC_DAY => __( 'On Specific Day' ),
            default => __( 'Unknown Occurance' )
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}
