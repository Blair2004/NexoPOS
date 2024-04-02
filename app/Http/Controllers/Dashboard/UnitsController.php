<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\UnitCrud;
use App\Crud\UnitGroupCrud;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\UnitRequest;
use App\Http\Requests\UnitsGroupsRequest;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\DateService;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class UnitsController extends DashboardController
{
    public function __construct(
        protected UnitService $unitService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function postGroup( UnitsGroupsRequest $request )
    {
        return $this->unitService->createGroup( $request->all() );
    }

    public function putGroup( UnitsGroupsRequest $request, $id )
    {
        return $this->unitService->updateGroup( $id, $request->only( [ 'name', 'description' ] ) );
    }

    /**
     * Create a new unit
     *
     * @param Request
     * @return AsyncResponse
     */
    public function postUnit( UnitRequest $request )
    {
        return $this->unitService->createUnit( $request->only( [ 'name', 'description', 'group_id', 'value', 'base_unit' ] ) );
    }

    public function deleteUnitGroup( $id )
    {
        return $this->unitService->deleteCategory( $id );
    }

    public function deleteUnit( $id )
    {
        return $this->unitService->deleteUnit( $id );
    }

    public function get( $id = null )
    {
        return $this->unitService->get( $id );
    }

    public function getSiblingUnits( Unit $id )
    {
        return $this->unitService->getSiblingUnits(
            unit: $id
        );
    }

    /**
     * get all units assigned to a specified group
     *
     * @param int group id
     * @return array
     */
    public function getGroupUnits( $id )
    {
        return $this->unitService->getGroups( $id )->units;
    }

    /**
     * Get group units using a specific id
     *
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
     *
     * @param Request
     * @param int unit id
     * @return json
     */
    public function putUnit( UnitRequest $request, $id )
    {
        return $this->unitService->updateUnit(
            $id,
            $request->only( [ 'name', 'description', 'group_id', 'value', 'base_unit' ] )
        );
    }

    /**
     * retrieve a group of a
     * specific unit using an id
     *
     * @param int Parent Group
     * @return json
     */
    public function getUnitParentGroup( $id )
    {
        return $this->unitService->getUnitParentGroup( $id );
    }

    public function listUnitsGroups()
    {
        ns()->restrict( [ 'nexopos.read.products-units' ] );

        return UnitGroupCrud::table();
    }

    public function listUnits()
    {
        ns()->restrict( [ 'nexopos.read.products-units' ] );

        return UnitCrud::table();
    }

    public function createUnitGroup()
    {
        ns()->restrict( [ 'nexopos.create.products-units' ] );

        return UnitGroupCrud::form();
    }

    /**
     * Edit existing unit group
     *
     * @return View
     */
    public function editUnitGroup( UnitGroup $group )
    {
        ns()->restrict( [ 'nexopos.update.products-units' ] );

        return UnitGroupCrud::form( $group );
    }

    public function createUnit()
    {
        ns()->restrict( [ 'nexopos.create.products-units' ] );

        return UnitCrud::form();
    }

    public function editUnit( Unit $unit )
    {
        ns()->restrict( [ 'nexopos.update.products-units' ] );

        return UnitCrud::form( $unit );
    }
}
