<?php

namespace App\Http\Requests;

use App\Services\CrudService;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudPutRequest extends BaseCrudRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $service = new CrudService;
        $resource = $service->getCrudInstance( $this->route( 'namespace' ) );

        /**
         * We do pass the model here as
         * it might be used to ignore record
         * during validation.
         */
        $arrayRules = $resource->extractValidation(
            model: $resource->getModel()::find( $this->route( 'id' ) )
        );

        /**
         * As validation might uses array with Rule class, we want to
         * properly exclude that, so that the array is not converted into dots.
         */
        $isolatedRules = $resource->isolateArrayRules( $arrayRules );

        /**
         * This will flat the rules to create a dot-like
         * validation rules array
         */
        $flatRules = collect( $isolatedRules )->mapWithKeys( function ( $rule ) {
            return [ $rule[0] => $rule[1] ];
        } )->toArray();

        return Hook::filter( 'ns.validation.' . $this->route( 'namespace' ), $flatRules );
    }
}
