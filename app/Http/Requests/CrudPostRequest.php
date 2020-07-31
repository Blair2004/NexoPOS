<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Services\Helper;
use Hook;
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
        $resource       =   $service->getCrudInstance( $this->route( 'namespace' ) );
        $arrayRules     =   $service->extractCrudValidation( $resource );
        $isolatedRules  =   $service->isolateArrayRules( $arrayRules );
        $flatRules      =   collect( $isolatedRules )->mapWithKeys( function( $rule ) {
            return [ $rule[0] => $rule[1] ];
        })->toArray();
        return Hook::filter( 'ns.validation.' . $this->route( 'namespace' ), $flatRules );
    }
}
