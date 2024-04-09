<?php

namespace App\Http\Requests;

use App\Crud\ProductCrud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

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

    public function messages()
    {
        return [
            'variations.0.units.selling_group.0.convert_unit_id.different' => __( 'The conversion unit and the unit cannot be the same. Double check your units tab and try again.' ),
        ];
    }
}
