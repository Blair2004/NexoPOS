<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Services\Helper;
use TorMorten\Eventy\Facades\Events as Hook;
use App\Crud\Applications;
use App\Exceptions\CoreException;
use App\Services\CrudService;
use Illuminate\Support\Arr;

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
        $service        =   new CrudService;

        /**
         * Let's initialize the CRUD resource
         */
        $resource       =   $service->getCrudInstance( $this->route( 'namespace' ) );

        /**
         * Now let's extract the validation rules
         * from the getForm method.
         */
        $arrayRules     =   $service->extractCrudValidation( $resource );

        /**
         * As rules might contains complex array (with Rule class),
         * we don't want that array to be transformed using the dot key form.
         */
        $isolatedRules  =   $service->isolateArrayRules( $arrayRules );

        /**
         * Let's properly flat everything.
         */
        $flatRules      =   collect( $isolatedRules )->mapWithKeys( function( $rule ) {
            return [ $rule[0] => $rule[1] ];
        })->toArray();

        return Hook::filter( 'ns.validation.' . $this->route( 'namespace' ), $flatRules );
    }
}
