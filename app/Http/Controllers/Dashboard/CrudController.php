<?php
namespace App\Http\Controllers\Dashboard;

use Tendoo\Core\Http\Controllers\Dashboard\CrudController as TendooCrudController;
use Tendoo\Core\Http\Requests\CrudPostRequest;
use Tendoo\Core\Http\Requests\CrudPutRequest;

class CrudController extends TendooCrudController
{
    public function list( $namespace )
    {
        return $this->crudList( 'nexopos-' . $namespace );
    }

    public function post( $namespace, CrudPostRequest $request )
    {
        return $this->crudPost( 'nexopos-' . $namespace, $request );
    }

    public function delete( $namespace, $index )
    {
        return $this->crudDelete( 'nexopos-' . $namespace, $index );
    }

    public function put( $namespace, $index, CrudPutRequest $request )
    {
        return $this->crudPut( 'nexopos-' . $namespace, $index, $request );
    }
}