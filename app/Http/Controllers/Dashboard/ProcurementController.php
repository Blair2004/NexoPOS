<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\ProcurementCrud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\Validation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Fields\ProcurementFields;
use App\Http\Controllers\DashboardController;
use App\Services\ProcurementService;
use App\Http\Requests\ProcurementRequest;
use App\Models\Procurement;
use Tendoo\Core\Exceptions\AccessDeniedException;


class ProcurementController extends DashboardController
{
    protected $crud;

    /** @param ProcurementService */
    protected $procurementService;

    public function __construct(
        ProcurementService $procurementService
    )
    {
        parent::__construct();

        $this->validation           =   new Validation;
        $this->procurementService   =   $procurementService;
    }

    /**
     * get a list of the procurements
     * @return CrudResponse
     */
    public function list()
    {
        return $this->procurementService->get();
    }

    /**
     * create a procurement
     * using the provided informations
     */
    public function create( ProcurementRequest $request )
    {
        return $this->procurementService->create( $request->only([
            'general', 'name', 'products'
        ]));
    }

    public function edit( $id, Request $request )
    {
        $rules              =   $this->validation
            ->from( ProcurementFields::class )
            ->extract( 'get', $this->procurementService->get( $id ) );

        $validationResult   =   Validator::make( 
            $request->all(), 
            $rules
        );

        return $this->procurementService->edit( $id, $request->only([
            'name', 'description', 'provider_id'
        ]) );
    }

    /**
     * procurement items
     * to the mentionned procurement
     * @param int procurement id
     * @param Request $request
     * @return array response
     */
    public function procure( $procurement_id, Request $request )
    {
        $procurement    =   $this->procurementService->get( $procurement_id );

        return $this->procurementService->procure(
            $procurement,
            collect( $request->input( 'items' ) )
        );
    }

    public function resetProcurement( $procurement_id )
    {
        return $this->procurementService->resetProcurement( $procurement_id );
    }

    /**
     * returns a procurement's products list
     * @param int procurement_id
     * @return array<ProcurementProduct>
     */
    public function procurementProducts( $procurement_id )
    {
        return $this->procurementService->getProducts( $procurement_id )->map( function( $product ) {
            $product->unit;
            return $product;
        });
    }

    /**
     * Edit a procurement product
     * using the procurement id, the product id and the data
     * @param Request $data
     * @param int procurement_id
     * @param int product_id
     * @return array response
     */
    public function editProduct( Request $request, $procurement_id, $product_id )
    {
        if ( $this->procurementService->hasProduct( $procurement_id, $product_id ) ) {
            return $this->procurementService->updateProcurementProduct( $product_id, $request->only([ 'quantity', 'unit_id', 'purchase_price' ] ) );
        }

        throw new AccessDeniedException( 
            sprintf( 
                __( 'The product which id is %s doesnt\'t belong to the procurement which id is %s' ),
                $product_id,
                $procurement_id
            )
        );
    }

    /**
     * Refresh a speciifc procurement manually
     * @param int procurement id
     * @return array response
     */
    public function refreshProcurement( $procurement_id )
    {
        return $this->procurementService->refresh( 
            $this->procurementService->get( $procurement_id ) 
        );
    }

    /**
     * Delete a procurement product
     * @param int product_id
     * @return array response
     */
    public function deleteProcurementProduct( $product_id )
    {
        return $this->procurementService->deleteProduct( $product_id );
    }

    /**
     * delete a specific procurement
     * using the provided id
     * @param int procurement id
     * @return array operation result
     */
    public function deleteProcurement( $procurement_id )
    {
        return $this->procurementService->delete( $procurement_id );
    }

    public function bulkUpdateProducts( $procurement_id, Request $request )
    {
        return $this->procurementService->bulkUpdateProducts( $procurement_id, $request->input( 'items' ) );
    }

    /**
     * Renders a table page for a procurement
     * @return Table
     */
    public function listProcurements()
    {
        return ProcurementCrud::table();
    }

    /**
     * Render a creation page for a procurement
     */
    public function createProcurement()
    {
        ns()->restrict([ 'nexopos.create.procurements' ]);

        return $this->view( 'pages.dashboard.procurements.create', [
            'title'         =>  __( 'New Procurement' ),
            'description'   =>  __( 'Make a new procurement' )
        ]);
    }

    public function updateProcurement( Procurement $procurement )
    {
        ns()->restrict([ 'nexopos.update.procurements' ]);

        return $this->view( 'pages.dashboard.procurements.edit', [
            'title'         =>  __( 'Edit Procurement' ),
            'description'   =>  __( 'Perform adjustment on existing procurement.' ),
            'procurement'   =>  $procurement
        ]);
    }
}

