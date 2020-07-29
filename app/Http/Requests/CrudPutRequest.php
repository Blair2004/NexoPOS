<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Services\CrudService;
use Hook;
use Illuminate\Support\Arr;

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
        $service    =   new CrudService;
        $resource   =   $service->getCrudInstance( $this->route( 'namespace' ) );
        return Hook::filter( 'ns.validation.' . $this->route( 'namespace' ), Arr::dot( $service->extractCrudValidation( $resource ) ) );
    }
}
