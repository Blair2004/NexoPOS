<?php

namespace App\Http\Requests;

use App\Services\CrudService;
use TorMorten\Eventy\Facades\Events as Hook;
use Illuminate\Foundation\Http\FormRequest;

class BaseCrudRequest extends FormRequest
{
    public function getPlainData( $namespace, $entry = null )
    {
        $service    =   new CrudService;
        $resource   =   $service->getCrudInstance( $this->route( 'namespace' ) );
        return $service->getPlainData( $resource, $this, $entry );
    }
}
