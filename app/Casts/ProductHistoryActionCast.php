<?php

namespace App\Casts;

use App\Models\ProductHistory;
use App\Services\CrudEntry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ProductHistoryActionCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get( Model|CrudEntry $model, string $key, mixed $value, array $attributes ): mixed
    {
        switch ( $value ) {
            case ProductHistory::ACTION_ADDED :
            case ProductHistory::ACTION_ADJUSTMENT_RETURN :
            case ProductHistory::ACTION_RETURNED :
            case ProductHistory::ACTION_STOCKED :
            case ProductHistory::ACTION_TRANSFER_CANCELED :
            case ProductHistory::ACTION_TRANSFER_IN :
            case ProductHistory::ACTION_VOID_RETURN :
                $model->{ '$cssClass' } = 'info border text-sm';
                break;
            default:
                $model->{ '$cssClass' } = 'success border text-sm';
                break;
        }

        return match ( $value ) {
            ProductHistory::ACTION_SET => __( 'Assignation' ),
            ProductHistory::ACTION_STOCKED => __( 'Stocked' ),
            ProductHistory::ACTION_DEFECTIVE => __( 'Defective' ),
            ProductHistory::ACTION_DELETED => __( 'Deleted' ),
            ProductHistory::ACTION_REMOVED => __( 'Removed' ),
            ProductHistory::ACTION_RETURNED => __( 'Returned' ),
            ProductHistory::ACTION_SOLD => __( 'Sold' ),
            ProductHistory::ACTION_LOST => __( 'Lost' ),
            ProductHistory::ACTION_ADDED => __( 'Added' ),
            ProductHistory::ACTION_TRANSFER_IN => __( 'Incoming Transfer' ),
            ProductHistory::ACTION_TRANSFER_OUT => __( 'Outgoing Transfer' ),
            ProductHistory::ACTION_TRANSFER_REJECTED => __( 'Transfer Rejected' ),
            ProductHistory::ACTION_TRANSFER_CANCELED => __( 'Transfer Canceled' ),
            ProductHistory::ACTION_VOID_RETURN => __( 'Void Return' ),
            ProductHistory::ACTION_ADJUSTMENT_RETURN => __( 'Adjustment Return' ),
            ProductHistory::ACTION_ADJUSTMENT_SALE => __( 'Adjustment Sale' ),
            ProductHistory::ACTION_CONVERT_IN => __( 'Incoming Conversion' ),
            ProductHistory::ACTION_CONVERT_OUT => __( 'Outgoing Conversion' ),
            default => __( 'Unknown Action' ),
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set( Model $model, string $key, mixed $value, array $attributes ): mixed
    {
        return $value;
    }
}
