<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;


use Tendoo\Core\Exceptions\NotFoundException;
use Exception;

use App\Http\Requests\UnitsGroupsRequest;
use App\Http\Requests\UnitRequest;
use App\Services\UnitService;


class UnitsController extends Controller
{
    private $unitService;

    public function __construct( UnitService $unit )
    {
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

    public function get()
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
}

