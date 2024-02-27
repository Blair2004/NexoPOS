<?php

namespace App\Casts;

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

        $value = match ( $value ) {
            'materialized' => __( 'Materialized' ),
            'dematerialized' => __( 'Dematerialized' ),
            'grouped' => __( 'Grouped' ),
            default => sprintf( __( 'Unknown Type: %s' ), $value ),
        };

        return '<strong class="' . $class . ' ">' . $value . '</strong>';
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
