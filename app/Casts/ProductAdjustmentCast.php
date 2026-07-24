<?php

namespace App\Casts;

use App\Services\ProductService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ProductAdjustmentCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model $model
     * @param  mixed $value
     * @return mixed
     */
    public function get( $model, string $key, $value, array $attributes )
    {
        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        return $productService->getAdjustmentLabel( $value );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model $model
     * @param  mixed $value
     * @return mixed
     */
    public function set( $model, string $key, $value, array $attributes )
    {
        return $value;
    }
}
