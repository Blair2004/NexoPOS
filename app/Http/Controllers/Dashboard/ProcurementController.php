<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
use App\Crud\ProcurementCrud;
use App\Crud\ProcurementProductCrud;
use App\Exceptions\NotAllowedException;
use Illuminate\Http\Request;
use App\Services\Validation;
use App\Http\Controllers\DashboardController;
use App\Services\ProcurementService;
use App\Services\Options;
use App\Http\Requests\ProcurementRequest;
use App\Jobs\ProcurementRefreshJob;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;


class ProcurementController extends DashboardController
{
    protected $crud;

    /** 
     * @var ProcurementService 
     **/
    protected $procurementService;

    /** 
     * @var ProductService 
     **/
    protected $productService;

    /**
     * @var Options
     */
    protected $options;

    public function __construct(
        ProcurementService $procurementService,
        ProductService $productService,
        Options $options
    )
    {
        parent::__construct();

        $this->validation           =   new Validation;
        $this->procurementService   =   $procurementService;
        $this->productService       =   $productService;
        $this->options              =   $options;
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

    public function edit( Procurement $procurement, ProcurementRequest $request )
    {
        if ( $procurement->delivery_status === Procurement::STOCKED ) {
            throw new NotAllowedException( __( 'Unable to edit a procurement that is stocked. Consider performing an adjustment or either delete the procurement.' ) );
        }

        return $this->procurementService->edit( $procurement->id, $request->only([
            'general', 'name', 'products'
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

        return $this->procurementService->saveProducts(
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

        throw new NotAllowedException( 
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
    public function refreshProcurement( Procurement $id )
    {
        ProcurementRefreshJob::dispatch( $id );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The refresh process has started. You\'ll get informed once it\'s complete.' )
        ];
    }

    /**
     * Delete a procurement product
     * @param int product_id
     * @return array response
     */
    public function deleteProcurementProduct( $product_id )
    {
        $procurementProduct     =   ProcurementProduct::find( $product_id );

        return $this->procurementService->deleteProduct( 
            $procurementProduct,
            $procurementProduct->procurement
        );
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

        return $this->view( 'pages.dashboard.procurements.create', Hook::filter( 'ns-create-procurement-labels', [
            'title'         =>  __( 'New Procurement' ),
            'description'   =>  __( 'Make a new procurement.' )
        ] ) );
    }

    public function updateProcurement( Procurement $procurement )
    {
        ns()->restrict([ 'nexopos.update.procurements' ]);

        if ( $procurement->delivery_status === Procurement::STOCKED ) {
            throw new NotAllowedException( __( 'Unable to edit a procurement that is stocked. Consider performing an adjustment or either delete the procurement.' ) );
        }

        return $this->view( 'pages.dashboard.procurements.edit', Hook::filter( 'ns-update-procurement-labels', [
            'title'         =>  __( 'Edit Procurement' ),
            'description'   =>  __( 'Perform adjustment on existing procurement.' ),
            'procurement'   =>  $procurement
        ] ) );
    }

    public function procurementInvoice( Procurement $procurement )
    {
        ns()->restrict([ 'nexopos.read.procurements' ]);

        return $this->view( 'pages.dashboard.procurements.invoice', [
            'title'         =>  sprintf( __( '%s - Invoice' ), $procurement->name ),
            'description'   =>  __( 'list of product procured.' ),
            'procurement'   =>  $procurement,
            'options'       =>  $this->options
        ]);
    }

    public function searchProduct( Request $request )
    {
        return $this->procurementService->searchProduct( $request->input( 'search' ) );
    }

    public function searchProcurementProduct( Request $request )
    {
        $products    =   Product::query()
            ->trackingDisabled()
            ->with( 'unit_quantities.unit' )
            ->where( function( $query ) use ( $request ) {
                $query->where( 'sku', 'LIKE', "%{$request->input( 'argument' )}%" )
                ->orWhere( 'name', 'LIKE', "%{$request->input( 'argument' )}%" )
                ->orWhere( 'barcode', 'LIKE', "%{$request->input( 'argument' )}%" );
            })
            ->limit( 8 )
            ->get()
            ->map( function( $product ) {
                $units  =   json_decode( $product->purchase_unit_ids );
                
                if ( $units ) {
                    $product->purchase_units     =   collect();
                    collect( $units )->each( function( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    });
                }

                return $product;
            });

        if ( ! $products->isEmpty() ) {
            return [
                'from'      =>  'products',
                'products'  =>  $products
            ];
        } 

        return [
            'from'      =>  'procurements',
            'product'   =>  $this->procurementService->searchProcurementProduct( $request->input( 'search' ) )
        ];
    }

    public function getProcurementProducts()
    {
        return ProcurementProductCrud::table();
    }

    public function editProcurementProduct( ProcurementProduct $product )
    {
        return ProcurementProductCrud::form( $product );
    }
}

