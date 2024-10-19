<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
use App\Crud\ProcurementCrud;
use App\Crud\ProcurementProductCrud;
use App\Exceptions\NotAllowedException;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\ProcurementRequest;
use App\Jobs\ProcurementRefreshJob;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\Unit;
use App\Services\DateService;
use App\Services\Options;
use App\Services\ProcurementService;
use App\Services\ProductService;
use App\Services\Validation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ProcurementController extends DashboardController
{
    protected $crud;

    protected $validation;

    public function __construct(
        protected ProcurementService $procurementService,
        protected ProductService $productService,
        protected Options $options,
        protected DateService $dateService
    ) {
        $this->validation = new Validation;
    }

    /**
     * get a list of the procurements
     *
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
        return $this->procurementService->create( $request->only( [
            'general', 'name', 'products',
        ] ) );
    }

    public function edit( Procurement $procurement, ProcurementRequest $request )
    {
        if ( $procurement->delivery_status === Procurement::STOCKED ) {
            throw new NotAllowedException( __( 'Unable to edit a procurement that is stocked. Consider performing an adjustment or either delete the procurement.' ) );
        }

        return $this->procurementService->edit( $procurement->id, $request->only( [
            'general', 'name', 'products',
        ] ) );
    }

    /**
     * procurement items
     * to the mentionned procurement
     *
     * @param int procurement id
     * @return array response
     */
    public function procure( $procurement_id, Request $request )
    {
        $procurement = $this->procurementService->get( $procurement_id );

        return $this->procurementService->saveProducts(
            $procurement,
            collect( $request->input( 'items' ) )
        );
    }

    /**
     * returns a procurement's products list
     *
     * @param int procurement_id
     * @return Collection
     */
    public function procurementProducts( $procurement_id )
    {
        return $this->procurementService->getProducts( $procurement_id )->map( function ( $product ) {
            $product->unit;

            return $product;
        } );
    }

    /**
     * Will change the payment status
     * for a procurement.
     */
    public function changePaymentStatus( Procurement $procurement, Request $request )
    {
        if ( $procurement->payment_status === Procurement::PAYMENT_PAID ) {
            throw new NotAllowedException( __( 'You cannot change the status of an already paid procurement.' ) );
        }

        $procurement->payment_status = $request->input( 'payment_status' );
        $procurement->save();

        return [
            'status' => 'success',
            'message' => __( 'The procurement payment status has been changed successfully.' ),
        ];
    }

    /**
     * Will change the payment status to
     * paid for a provided procurement.
     *
     * @return array
     */
    public function setAsPaid( Procurement $procurement )
    {
        if ( $procurement->payment_status === Procurement::PAYMENT_PAID ) {
            throw new NotAllowedException( __( 'You cannot change the status of an already paid procurement.' ) );
        }

        $procurement->payment_status = Procurement::PAYMENT_PAID;
        $procurement->save();

        return [
            'status' => 'success',
            'message' => __( 'The procurement has been marked as paid.' ),
        ];
    }

    /**
     * Edit a procurement product
     * using the procurement id, the product id and the data
     *
     * @param Request $data
     * @param int procurement_id
     * @param int product_id
     * @return array response
     */
    public function editProduct( Request $request, $procurement_id, $product_id )
    {
        if ( $this->procurementService->hasProduct( $procurement_id, $product_id ) ) {
            return $this->procurementService->updateProcurementProduct( $product_id, $request->only( [ 'quantity', 'unit_id', 'purchase_price' ] ) );
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
     *
     * @param int procurement id
     * @return array response
     */
    public function refreshProcurement( Procurement $id )
    {
        ProcurementRefreshJob::dispatch( $id );

        return [
            'status' => 'success',
            'message' => __( 'The refresh process has started. You\'ll get informed once it\'s complete.' ),
        ];
    }

    /**
     * Delete a procurement product
     *
     * @param int product_id
     * @return array response
     */
    public function deleteProcurementProduct( $product_id )
    {
        $procurementProduct = ProcurementProduct::find( $product_id );

        return $this->procurementService->deleteProduct(
            $procurementProduct,
            $procurementProduct->procurement
        );
    }

    /**
     * delete a specific procurement
     * using the provided id
     *
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
     *
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
        ns()->restrict( [ 'nexopos.create.procurements' ] );

        return View::make( 'pages.dashboard.procurements.create', Hook::filter( 'ns-create-procurement-labels', [
            'title' => __( 'New Procurement' ),
            'description' => __( 'Make a new procurement.' ),
        ] ) );
    }

    public function updateProcurement( Procurement $procurement )
    {
        ns()->restrict( [ 'nexopos.update.procurements' ] );

        if ( $procurement->delivery_status === Procurement::STOCKED ) {
            throw new NotAllowedException( __( 'Unable to edit a procurement that is stocked. Consider performing an adjustment or either delete the procurement.' ) );
        }

        return View::make( 'pages.dashboard.procurements.edit', Hook::filter( 'ns-update-procurement-labels', [
            'title' => __( 'Edit Procurement' ),
            'description' => __( 'Perform adjustment on existing procurement.' ),
            'procurement' => $procurement,
        ] ) );
    }

    public function procurementInvoice( Procurement $procurement )
    {
        ns()->restrict( [ 'nexopos.read.procurements' ] );

        return View::make( 'pages.dashboard.procurements.invoice', [
            'title' => sprintf( __( '%s - Invoice' ), $procurement->name ),
            'description' => __( 'list of product procured.' ),
            'procurement' => $procurement,
            'options' => $this->options,
        ] );
    }

    public function searchProduct( Request $request )
    {
        return $this->procurementService->searchProduct( $request->input( 'search' ) );
    }

    public function searchProcurementProduct( Request $request )
    {
        $products = Product::query()
            ->trackingDisabled()
            ->withStockEnabled()
            ->notGrouped()
            ->with( 'unit_quantities.unit' )
            ->where( function ( $query ) use ( $request ) {
                $query->where( 'sku', 'LIKE', "%{$request->input( 'argument' )}%" )
                    ->orWhere( 'name', 'LIKE', "%{$request->input( 'argument' )}%" )
                    ->orWhere( 'barcode', 'LIKE', "%{$request->input( 'argument' )}%" );
            } )
            ->limit( 8 )
            ->get()
            ->map( function ( $product ) {
                $units = json_decode( $product->purchase_unit_ids );

                if ( $units ) {
                    $product->purchase_units = collect();
                    collect( $units )->each( function ( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    } );
                }

                return $product;
            } );

        if ( ! $products->isEmpty() ) {
            return [
                'from' => 'products',
                'products' => $products,
            ];
        }

        return [
            'from' => 'procurements',
            'product' => $this->procurementService->searchProcurementProduct( $request->input( 'argument' ) ),
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
