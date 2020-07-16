<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;
use Tendoo\Core\Exceptions\NotFoundException;
use Tendoo\Core\Exceptions\NotAllowedException;

use App\Models\Tax;
use App\Services\TaxService;

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
                throw new NotFoundException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to find the requeted product tax using the provided id' )
                ]);
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
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the requested product tax using the provided identifier.' )
            ]);
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
    public function subTaxes( $taxId )
    {
        $tax    =   Tax::find( $taxId );

        if ( ! $tax instanceof Tax ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the parent taxes using the provided id.' )
            ]);
        }

        if ( $tax->type === 'simple' ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find sub taxes from a tax with a simple form.' )
            ]);
        }

        return $tax->subTaxes;
    }

    /**
     * Get Model Schema
     * which describe the field expected on post/put
     * requests
     * @return json
     */
    public function schema()
    {
        return [
            'name'          =>  'string',
            'description'   =>  'string',
            'media_id'      =>  'number',
            'parent_id'     =>  'number'
        ];
    }
}

