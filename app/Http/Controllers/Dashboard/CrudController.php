<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controller\CrudController as BaseCrudController;

class CrudController extends BaseCrudController
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