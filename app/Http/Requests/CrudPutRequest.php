<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Facades\Hook;
use App\Exceptions\CoreException;

class CrudPutRequest extends FormRequest
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
        /**
         * get resource defined
         */
        $crudClass  =   Hook::filter( 'register.crud', $this->route( 'namespace' ) );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new CoreException([
                'message'   =>  __( 'Unhandled Crud resource' )
            ]);
        }
        
        $resource   =   new $crudClass;

        if ( is_object( $resource ) ) {
            return $resource->validationRules( $this );      
        }

        // if a resource is not defined. Let's return an empty array.
        return [];
    }
}
