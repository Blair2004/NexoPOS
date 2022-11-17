<?php

namespace App\Casts;

use App\Models\Expense;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ExpenseTypeCast implements CastsAttributes
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
            Expense::TYPE_DIRECT    =>  __( 'Direct Expense' ),
            Expense::TYPE_RECURRING    =>  __( 'Recurring Expense' ),
            Expense::TYPE_SALARY    =>  __( 'Salary Expense' ),
            Expense::TYPE_SCHEDULED    =>  __( 'Scheduled Expense' ),
            default => __( 'Unknown Type' ),
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
