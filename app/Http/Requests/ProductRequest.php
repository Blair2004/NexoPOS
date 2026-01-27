<?php

namespace App\Http\Requests;

use App\Crud\ProductCrud;
use App\Models\ProductUnitQuantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $crudInstance = new ProductCrud;
        $product = $this->route( 'product' );
        $extractValidation = $crudInstance->extractProductValidaton( $product );
        $rules = Arr::dot( $extractValidation );

        return $rules;
    }

    public function attributes()
    {
        return [
            'variations.*.units.selling_group.*.convert_unit_id' => __( 'Convert Unit' ),
            'variations.*.units.selling_group.*.unit_id' => __( 'Unit' ),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator( Validator $validator ): void
    {
        $validator->after( function ( $validator ) {
            $this->validateScalePLUs( $validator );
        } );
    }

    /**
     * Validate scale PLUs for uniqueness and proper configuration.
     */
    protected function validateScalePLUs( $validator ): void
    {
        $variations = $this->input( 'variations', [] );
        $product = $this->route( 'product' );

        foreach ( $variations as $variationIndex => $variation ) {
            $sellingGroup = $variation['units']['selling_group'] ?? [];

            foreach ( $sellingGroup as $unitIndex => $unit ) {
                $scalePLU = $unit['scale_plu'] ?? null;
                $isWeighable = $unit['is_weighable'] ?? false;
                $unitId = $unit['id'] ?? null;

                // Validate PLU uniqueness if provided
                if ( ! empty( $scalePLU ) ) {
                    $query = ProductUnitQuantity::where( 'scale_plu', $scalePLU );

                    // Exclude current unit quantity if updating
                    if ( $unitId ) {
                        $query->where( 'id', '!=', $unitId );
                    }

                    if ( $query->exists() ) {
                        $validator->errors()->add(
                            "variations.{$variationIndex}.units.selling_group.{$unitIndex}.scale_plu",
                            __( 'The PLU code :plu is already in use by another product unit.', [ 'plu' => $scalePLU ] )
                        );
                    }
                }
            }
        }
    }
}
