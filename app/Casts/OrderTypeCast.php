<?php

namespace App\Casts;

use App\Services\OrdersService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class OrderTypeCast implements CastsAttributes
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
        /**
         * @var OrdersService $orderService
         */
        $orderService = app()->make( OrdersService::class );

        return $orderService->getTypeLabel( $value );
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
