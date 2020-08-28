<?php 
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Auth;

use App\Exceptions\NotFoundException;
use App\Exceptions\NotAllowedException;

use App\Models\Product;
use App\Models\ProductTax;
use App\Models\Tax;


class TaxService
{
    /** @param CurrencyService */
    protected $currency;

    public function __construct( CurrencyService $currency )
    {
        $this->currency     =   $currency;
    }

    private function __checkTaxParentExists( $parent )
    {
        if ( ! $parent instanceof Tax ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to proceed. The parent tax doesn\'t exists.' )
            ]);
        }
    }

    /**
     * check the validity of a tax parent
     * while creating
     * @param Tax parent tax
     * @return void
     */
    private function __checkTaxParentOnCreation( Tax $parent )
    {
        if ( $parent->type !== 'grouped' ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'A simple tax must not be assigned to a parent tax with the type "simple", but "grouped" instead.' )
            ]);
        }

        if ( ! $parent instanceof Tax ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to proceed. The parent tax doesn\'t exists.' )
            ]);
        }
    }

    /**
     * checks if a tax parent id
     * matches the current tax id
     * @param Tax
     * @param int tax id
     * @return void
     */
    private function __checkTaxParentOnModification( Tax $parent, $id )
    {
        if ( $parent->id === $id ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'A tax cannot be his own parent.' )
            ]);
        }
    }

    /**
     * checks if the tax provided is 
     * not a grouped tax
     * @param array tax
     * @return void
     */
    private function __checkIfNotGroupedTax( $fields )
    {
        if ( $fields[ 'type' ] === 'grouped' ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'The tax hierarchy is limited to 1. A sub tax must not have the tax type set to "grouped".' )
            ]);
        }
    }

    /**
     * get a specific tax
     * using the provided id
     * @param int tax id
     * @return Tax
     */
    public function get( $tax_id )
    {
        $tax    =   Tax::find( $tax_id );
        if ( ! $tax instanceof Tax ) {
            throw new Exception( __( 'Unable to find the requested tax using the provided identifier.' ) );
        }

        return $tax;
    }

    /**
     * create a tax using the provided informations
     * @param array tax fields
     * @return Tax
     */
    public function create( $fields )
    {
        /**
         * Check if the parent tax exists
         */
        if ( isset( $fields[ 'parent_id' ] ) ) {
            $parent         =   Tax::find( $fields[ 'parent_id' ] );

            $this->__checkTaxParentExists( $parent );
            $this->__checkTaxParentOnCreation( $parent );
            $this->__checkIfNotGroupedTax( $fields );
        }  

        /**
         * @todo check circular hierarchy
         */
        $tax     =   new Tax;

        foreach( $fields as $field => $value ) {
            $tax->$field     =   $value;
        }

        $tax->author         =   Auth::id();
        $tax->save();

        return $tax;
    }

    /**
     * Update a provided tax using
     * the identifier and the data
     * @param int tax id
     * @param array tax data
     * @return Tax
     */
    public function update( $id, $fields )
    {
        /**
         * Check if the parent tax exists
         */
        if ( isset( $fields[ 'parent_id' ] ) ) {
            $parent         =   Tax::find( $fields[ 'parent_id' ] );

            $this->__checkTaxParentExists( $parent );
            $this->__checkTaxParentOnCreation( $parent );
            $this->__checkTaxParentOnModification( $parent, $id );
            $this->__checkIfNotGroupedTax( $fields );
        }  

        /**
         * @todo check circular hierarchy
         */
        $tax     =   $this->get( $id );

        foreach( $fields as $field => $value ) {
            $tax->$field     =   $value;
        }

        $tax->author         =   Auth::id();
        $tax->save();

        return $tax;
    }

    /**
     * compute the tax added to a 
     * product
     */
    public function computeTax( Product $product, $tax_id )
    {
        $tax        =   $this->get( $tax_id );

        /**
         * @todo needs to handle for multiple taxes | grouped taxes
         */
        if ( $tax->type === 'simple' && $product->sale_price > 0 ) {
            $taxValue   =   $this->currency->value( $product->sale_price )
                ->multiplyBy( $tax->rate )
                ->divideBy(100)
                ->get();

            if ( $product->tax_type === 'inclusive' ) {
                $product->net_sale_price    =   $product->sale_price;
                $product->gross_sale_price  =   $this->currency->value( $product->sale_price )
                    ->subtractBy( $taxValue )
                    ->get();
            } else if ( $product->tax_type === 'exclusive' ) {
                $product->net_sale_price    =   $this->currency->value( $product->sale_price )
                    ->additionateBy( $taxValue )
                    ->get();
                $product->gross_sale_price  =   $this->currency->value( $product->sale_price )->get();
            }

            $product->save();

            /**
             * update product tax
             */
            $this->saveProductTax( $product, $tax );
        }
    }

    /**
     * Update the product tax using
     * the provided tax/product combinaison
     * @param Product product itself
     * @param Tax tax itself
     * @return array
     */
    public function saveProductTax( Product $product, Tax $tax )
    {
        $taxValue   =   $this->currency->value( $product->sale_price )
            ->multiplyBy( $tax->rate )
            ->divideBy( 100 )
            ->get();

        $product->tax_value     =   $taxValue;
        $product->save();

        /**
         * let's update or add a ProductTax entry.
         * We assume there can only be unique 
         * tax_id & product_id combinaison
         */
        $productTax     =   ProductTax::findMatch([
            'product_id'    =>  $product->id,
            'tax_id'        =>  $tax->id
        ])->first();

        if ( $productTax instanceof ProductTax ) {
            $productTax->name       =   $tax->name; // in case it has changed
            $productTax->rate       =   $tax->rate;
            $productTax->value      =   $taxValue;
            $productTax->author     =   Auth::id();
            $productTax->save();
        } else {
            $productTax                 =   new ProductTax;
            $productTax->product_id     =   $product->id;
            $productTax->tax_id         =   $tax->id;
            $productTax->name           =   $tax->name; // in case it has changed
            $productTax->rate           =   $tax->rate;
            $productTax->value          =   $taxValue;
            $productTax->author         =   Auth::id();
            $productTax->save();
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product tax has been saved.' ),
            'data'      =>  [
                'tax'   =>  $productTax
            ]
        ];
    }

    /**
     * delete a specific tax using 
     * a provided identifier
     * @param int tax id
     * @return array|Exception response of the operation
     */
    public function delete( $id )
    {
        $tax        =   $this->get( $id );
        $tax->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The tax has been successfully deleted.' )
        ];
    }
}