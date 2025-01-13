<?php

namespace App\Casts;

use App\Classes\Hook;
use App\Services\CrudEntry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ProductTypeCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get( Model|CrudEntry $model, string $key, mixed $value, array $attributes ): mixed
    {
        $class = match ( $value ) {
            'grouped' => 'text-success-tertiary',
            default => 'text-info-tertiary'
        };

        $productTypes = Hook::filter( 'ns-products-type', [
            'materialized' => __( 'Materialized Product' ),
            'dematerialized' => __( 'Dematerialized Product' ),
            'grouped' => __( 'Grouped Product' ),
        ] );

        if ( isset( $productTypes[ $value ] ) ) {
            return '<strong class="' . $class . ' ">' . $productTypes[ $value ] . '</strong>';
        } else {
            return '<strong class="' . $class . ' ">' . sprintf( __( 'Unknown Type: %s' ), $value ) . '</strong>';
        }
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
