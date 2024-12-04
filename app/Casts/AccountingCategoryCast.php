<?php

namespace App\Casts;

use App\Services\CrudEntry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AccountingCategoryCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get( Model|CrudEntry $model, string $key, mixed $value, array $attributes ): mixed
    {
        $accounting = config( 'accounting.accounts' );

        $accountReference = $accounting[ $value ] ?? null;

        if ( $accountReference ) {
            return $accountReference[ 'label' ]();
        }

        return $value;
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
