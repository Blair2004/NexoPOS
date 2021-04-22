<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\TaxCrud;
use App\Crud\TaxesGroupCrud;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;

use App\Models\Tax;
use App\Models\TaxGroup;
use App\Services\TaxService;
use Exception;

class TaxesController extends DashboardController
{
    private $taxService;

    public function __construct(
        TaxService $taxService
    )
    {
        parent::__construct();

        $this->taxService   =   $taxService;
    }

    public function get( $id = null )
    {
        if ( ! empty( $id ) ) {
            $productTax   =   Tax::find( $id );
            if( ! $productTax instanceof Tax ) {
                throw new Exception( __( 'Unable to find the requeted product tax using the provided id' ) );
            }
            return $productTax;
        }

        return Tax::get();
    }

    /**
     * try to delete a category using the provided
     * id
     * @param number id
     * @return json
     */
    public function delete( $id )
    {
        return $this->taxService->delete( $id );
    }

    /**
     * Internal feature to get product
     * tax or to fail
     * @param integer tax id
     * @return Tax | null
     */
    private function getTaxOrFail( $id )
    {
        $productTax     =   Tax::find( $id );
        if ( ! $productTax instanceof Tax ) {
            throw new Exception( __( 'Unable to find the requested product tax using the provided identifier.' ) );
        }
        return $productTax;
    }

    /**
     * create a category using the provided
     * form data
     * @param request
     * @return json
     */
    public function post( Request $request ) // must be a specific form request with a validation
    {
        /**
         * @todo add a prior validation
         * on this element and check the permisions.
         * The validation should check wether the type is "grouped" or "simple"
         */
        $fields     =   $request->only([
            'name', 'rate', 'description', 'type', 'parent_id'
        ]);

        $this->taxService->create( $fields );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product tax has been created.' )
        ];
    }

    /**
     * edit existing category using
     * the provided ID
     * @param int category id
     * @return json
     */
    public function put( $id, Request $request ) // must use a specific request which include a validation
    {
        $fields     =   $request->only([
            'name', 'rate', 'description', 'type', 'parent_id'
        ]);

        $tax    =   $this->taxService->update( $id, $fields );
        
        /**
         * @todo dispatch en event
         * mentionning the edited tax
         */
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product tax has been updated' ),
            'data'      =>  compact( 'tax' )
        ];
    }

    /**
     * Get sub taxes
     * @param int tax id
     * @return json
     */
    public function getTaxGroup( $taxId = null )
    {
        if ( $taxId === null ) {
            return TaxGroup::with( 'taxes' )->get();
        }

        $taxGroup    =   TaxGroup::find( $taxId );

        if ( ! $taxGroup instanceof TaxGroup ) {
            throw new NotFoundException( sprintf(
                __( 'Unable to retreive the requested tax group using the provided identifier "%s".' ),
                $taxId
            ) );
        }

        $taxGroup->load( 'taxes' );
        
        return $taxGroup;
    }

    /**
     * List all available taxes
     * @return view
     */
    public function listTaxes()
    {
        return TaxCrud::table();
    }

    /**
     * Create new taxes
     * @return view
     */
    public function createTax()
    {
        return TaxCrud::form();
    }

    /**
     * Edit existing taxes
     * @param Tax $tax
     * @return view
     */
    public function editTax( Tax $tax )
    {
        return TaxCrud::form( $tax );
    }

    /**
     * Create tax groups
     * @return view
     */
    public function taxesGroups()
    {
        return TaxesGroupCrud::table();
    }

    /**
     * Create tax groups
     * @return view
     */
    public function createTaxGroups()
    {
        return TaxesGroupCrud::form();
    }

    /**
     * Edit tax groups
     * @return view
     */
    public function editTaxGroup( TaxGroup $group )
    {
        return TaxesGroupCrud::form( $group );
    }
}

