<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Requests\UnitsGroupsRequest;
use App\Http\Requests\UnitRequest;
use App\Models\ProductUnitQuantity;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Crud\UnitCrud;
use App\Crud\UnitGroupCrud;
use App\Services\UnitService;
use Illuminate\Http\Request;

class UnitsController extends DashboardController
{
    private $unitService;

    public function __construct( UnitService $unit )
    {
        parent::__construct();
        $this->unitService  =   $unit;
    }

    public function postGroup( UnitsGroupsRequest $request )
    {   
        return $this->unitService->createGroup( $request->all() );
    }

    public function putGroup( UnitsGroupsRequest $request, $id )
    {
        return $this->unitService->updateGroup( $id, $request->only([ 'name', 'description' ]) );
    }

    /**
     * Create a new unit
     * @param Request
     * @return AsyncResponse
     */
    public function postUnit( UnitRequest $request )
    {
        return $this->unitService->createUnit( $request->only([ 'name', 'description', 'group_id', 'value', 'base_unit' ]) );
    }

    public function deleteUnitGroup( $id )
    {
        return $this->unitService->deleteCategory( $id );
    }

    public function deleteUnit( $id )
    {
        return $this->unitService->deleteUnit( $id );
    }

    public function get( Request $request )
    {
        return $this->unitService->get();
    }

    /**
     * get all units assigned to a specified group
     * @param int group id
     * @return array
     */
    public function getGroupUnits( $id ) 
    {
        return $this->unitService->getGroups( $id )->units;
    }

    
    /**
     * Get group units using a specific id
     * @param int group id
     * @return array or units
     */
    public function getGroups()
    {
        return $this->unitService->getGroups();
    }

    /**
     * Edit a unit using provided
     * informations
     * @param Request
     * @param int unit id
     * @return json
     */
    public function putUnit( UnitRequest $request, $id )
    {
        return $this->unitService->updateUnit( 
            $id, 
            $request->only([ 'name', 'description', 'group_id', 'value', 'base_unit' ]) 
        );
    }

    /**
     * retrieve a group of a 
     * specific unit using an id
     * @param int Parent Group
     * @return json
     */
    public function getUnitParentGroup( $id )
    {
        return $this->unitService->getUnitParentGroup( $id );
    }

    public function listUnitsGroups()
    {
        ns()->restrict([ 'nexopos.read.products-units' ]);

        return UnitGroupCrud::table();
    }

    public function listUnits()
    {
        ns()->restrict([ 'nexopos.read.products-units' ]);

        return UnitCrud::table();
    }

    public function createUnitGroup()
    {
        ns()->restrict([ 'nexopos.create.products-units' ]);

        return UnitGroupCrud::form();
    }

    /**
     * Edit existing unit group
     * @param UnitGroup $group
     * @return View
     */
    public function editUnitGroup( UnitGroup $group )
    {
        ns()->restrict([ 'nexopos.update.products-units' ]);

        return UnitGroupCrud::form( $group );
    }

    public function createUnit()
    {
        ns()->restrict([ 'nexopos.create.products-units' ]);

        return UnitCrud::form();
    }

    public function editUnit( Unit $unit )
    {
        ns()->restrict([ 'nexopos.update.products-units' ]);

        return UnitCrud::form( $unit );
    }
}

