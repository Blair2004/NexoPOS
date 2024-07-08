<?php

namespace App\Http\Requests;

use App\Services\CrudService;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudPostRequest extends BaseCrudRequest
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

        /**
         * Let's initialize the CRUD resource
         */
        $resource = $service->getCrudInstance( $this->route( 'namespace' ) );

        /**
         * Now let's extract the validation rules
         * from the getForm method.
         */
        $arrayRules = $resource->extractValidation();

        /**
         * As rules might contains complex array (with Rule class),
         * we don't want that array to be transformed using the dot key form.
         */
        $isolatedRules = $resource->isolateArrayRules( $arrayRules );

        /**
         * Let's properly flat everything.
         */
        $flatRules = collect( $isolatedRules )->mapWithKeys( function ( $rule ) {
            return [ $rule[0] => $rule[1] ];
        } )->toArray();

        return Hook::filter( 'ns.validation.' . $this->route( 'namespace' ), $flatRules );
    }

    public function attributes()
    {
        return [
            'general.email' => __( 'Email' ),
        ];
    }
}
