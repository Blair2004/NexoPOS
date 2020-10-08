<?php 
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Auth;

use App\Exceptions\NotFoundException;
use App\Exceptions\NotAllowedException;

use App\Models\Product;
use App\Models\ProductTax;
use App\Models\Tax;
use App\Models\TaxGroup;

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
            throw new Exception( __( 'Unable to proceed. The parent tax doesn\'t exists.' ) );
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
            throw new Exception( __( 'A simple tax must not be assigned to a parent tax with the type "simple", but "grouped" instead.' ) );
        }

        if ( ! $parent instanceof Tax ) {
            throw new Exception( __( 'Unable to proceed. The parent tax doesn\'t exists.' ) );
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
            throw new Exception( __( 'A tax cannot be his own parent.' ) );
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
            throw new Exception( __( 'The tax hierarchy is limited to 1. A sub tax must not have the tax type set to "grouped".' ) );
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
     * Returns a single instance of a group
     * @param int group id
     * @return TaxGroup
     */
    public function getGroup( $group_id )
    {
        return TaxGroup::findOrFail( $group_id );
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
    public function computeTax( Product $product, $tax_group_id )
    {
        $taxGroup                               =   TaxGroup::find( $tax_group_id );

        $product->sale_price                    =   floatval( $product->sale_price_edit );
        $product->incl_tax_sale_price           =   floatval( $product->sale_price_edit );
        $product->incl_tax_wholesale_price      =   floatval( $product->wholesale_price_edit );

        /**
         * calculate the taxes wether they are all
         * inclusive or exclusive for the sale_price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxValue       =   $taxGroup->taxes
                ->map( function( $tax ) use ( $product ) {
                    $taxValue           =   $this->getVatValue(
                        $product->tax_type,
                        floatval( $tax[ 'rate' ] ),
                        $product->sale_price_edit
                    );

                    $productTax                 =   ProductTax::where( 'tax_id', $tax->id )
                        ->where( 'product_id', $product->id )
                        ->first();

                    /**
                     * if the tax hasn't yet been set, we'll create a new instance
                     */
                    if( ! $productTax instanceof ProductTax ) {
                        $productTax                 =   new ProductTax;
                    }

                    $productTax->product_id     =   $product->id;
                    $productTax->tax_id         =   $tax->id;
                    $productTax->rate           =   $tax->rate;
                    $productTax->name           =   $tax->name;
                    $productTax->author         =   Auth::id();
                    $productTax->value          =   $taxValue;
                    $productTax->save();

                    return $taxValue;
                })
                ->sum();

            $taxRate        =   $taxGroup->taxes
                ->map( function( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                })
                ->sum();

            if ( $product->tax_type === 'inclusive' ) {
                $product->excl_tax_sale_price       =   ( floatval( $product->sale_price_edit ) );
                $product->sale_tax_value            =   ( floatval( $this->getVatValue( 'inclusive', $taxRate, $product->sale_price_edit ) ) );
                $product->incl_tax_sale_price       =   $this->getComputedTaxValue(
                    'inclusive',
                    $taxRate,
                    $product->sale_price_edit
                );
            } else {
                $product->excl_tax_sale_price       =   floatval( $product->sale_price_edit );
                $product->sale_tax_value            =   ( floatval( $this->getVatValue( 'exclusive', $taxRate, $product->sale_price_edit ) ) );
                $product->incl_tax_sale_price       =   $this->getComputedTaxValue(
                    'exclusive',
                    $taxRate,
                    $product->sale_price_edit
                );
            }

            $product->tax_value                 =   $taxValue;
        }

        /**
         * calculate the taxes wether they are all
         * inclusive or exclusive for the wholesale price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxValue       =   $taxGroup->taxes
                ->map( function( $tax ) use ( $product ) {
                    $taxValue           =   ( floatval( $tax[ 'rate' ] ) * $product->wholesale_price_edit ) / 100;

                    $productTax                 =   new ProductTax;
                    $productTax->product_id     =   $product->id;
                    $productTax->tax_id         =   $tax->id;
                    $productTax->rate           =   $tax->rate;
                    $productTax->name           =   $tax->name;
                    $productTax->author         =   Auth::id();
                    $productTax->value          =   $taxValue;
                    $productTax->save();

                    return $taxValue;
                })
                ->sum();

            $taxRate        =   $taxGroup->taxes
                ->map( function( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                })
                ->sum();

            if ( $product->tax_type === 'inclusive' ) {
                $product->wholesale_tax_value               =   ( floatval( $this->getVatValue( 'inclusive', $taxRate, $product->wholesale_price_edit    ) ) );
                $product->excl_tax_wholesale_price            =   $this->getComputedTaxValue(
                    'inclusive',
                    $taxRate,
                    $product->wholesale_price_edit
                );
            } else {
                $product->wholesale_tax_value               =   ( floatval( $this->getVatValue( 'exclusive', $taxRate, $product->wholesale_price_edit    ) ) );
                $product->excl_tax_wholesale_price            =   floatval( $product->wholesale_price_edit );
                $product->incl_tax_wholesale_price            =   $this->getComputedTaxValue(
                    'exclusive',
                    $taxRate,
                    $product->wholesale_price_edit
                );
            }

            $product->tax_value                 =   $taxValue;
        }

        $product->save();
    }

    public function getComputedTaxValue( $type, float $rate, float $value )
    {
        if ( $type === 'inclusive' ) {
            return ( $value / ( $rate + 100 ) ) * 100;
        } else if ( $type === 'exclusive' ) {
            return ( $value / 100 ) * ( $rate + 100 );
        }
    }

    public function getVatValue( $type, float $rate, float $value )
    {
        if ( $type === 'inclusive' ) {
            return $value - $this->getComputedTaxValue( $type, $rate, $value );
        } else if ( $type === 'exclusive' ) {
            return $this->getComputedTaxValue( $type, $rate, $value ) - $value;
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