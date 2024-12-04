<?php

namespace App\Casts;

use App\Models\Transaction;
use App\Services\CrudEntry;
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
        if ( $model instanceof CrudEntry ) {
            if ( $model->getRawValue( 'type' ) === Transaction::TYPE_SCHEDULED ) {
                return sprintf(
                    __( 'Scheduled for %s' ),
                    ns()->date->getFormatted( $model->getRawValue( 'scheduled_date' ) )
                );
            }
        }

        if ( $value === null ) {
            return __( 'No Occurrence' );
        }

        if ( $value == 1 ) {
            $specificLabel = __( 'Every 1st of the month' );
        } elseif ( $value == 2 ) {
            $specificLabel = __( 'Every 2nd of the month' );
        } elseif ( $value == 3 ) {
            $specificLabel = __( 'Every 3rd of the month' );
        } else {
            $specificLabel = sprintf( __( 'Every %sth of the month' ), $value );
        }

        return match ( $value ) {
            Transaction::OCCURRENCE_START_OF_MONTH => __( 'Every start of the month' ),
            Transaction::OCCURRENCE_MIDDLE_OF_MONTH => __( 'Every middle month' ),
            Transaction::OCCURRENCE_END_OF_MONTH => __( 'Every end of month' ),
            Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => $model->occurrence_value <= 1 ? sprintf( __( 'Every %s day after month starts' ), $model->occurrence_value ) : sprintf( __( 'Every %s days after month starts' ), $model->occurrence_value ),
            Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS => $model->occurrence_value <= 1 ? sprintf( __( 'Every %s Days before month ends' ) ) : sprintf( __( 'Every %s Days before month ends' ), $model->occurrence_value ),
            Transaction::OCCURRENCE_SPECIFIC_DAY => $specificLabel,
            Transaction::OCCURRENCE_EVERY_X_DAYS => $model->occurrence_value <= 1 ? sprintf( __( 'Every %s day' ), $model->occurrence_value ) : sprintf( __( 'Every %s days' ), $model->occurrence_value ),
            Transaction::OCCURRENCE_EVERY_X_HOURS => $model->occurrence_value <= 1 ? sprintf( __( 'Every %s hour' ), $model->occurrence_value ) : sprintf( __( 'Every %s hours' ), $model->occurrence_value ),
            Transaction::OCCURRENCE_EVERY_X_MINUTES => $model->occurrence_value <= 1 ? sprintf( __( 'Every %s minute' ), $model->occurrence_value ) : sprintf( __( 'Every %s minutes' ), $model->occurrence_value ),
            default => sprintf( __( 'Unknown Occurance: %s' ), $value )
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
