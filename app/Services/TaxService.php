<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTax;
use App\Models\ProductUnitQuantity;
use App\Models\Tax;
use App\Models\TaxGroup;
use Exception;
use Illuminate\Support\Facades\Auth;

class TaxService
{
    public function __construct( protected CurrencyService $currency )
    {
        // ...
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
     *
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
     *
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
     *
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
     *
     * @param int tax id
     * @return Tax
     */
    public function get( $tax_id )
    {
        $tax = Tax::find( $tax_id );
        if ( ! $tax instanceof Tax ) {
            throw new Exception( __( 'Unable to find the requested tax using the provided identifier.' ) );
        }

        return $tax;
    }

    /**
     * That will return the first
     * tax using a specific name
     *
     * @param string $name
     * @return Tax
     */
    public function getUsingName( $name )
    {
        return Tax::where( 'name', $name )->first();
    }

    /**
     * That will return the first
     * tax using a specific name
     *
     * @param string $name
     * @return TaxGroup
     */
    public function getTaxGroupUsingName( $name )
    {
        return TaxGroup::where( 'name', $name )->first();
    }

    /**
     * Returns a single instance of a group
     *
     * @param int group id
     * @return TaxGroup
     */
    public function getGroup( $group_id )
    {
        return TaxGroup::findOrFail( $group_id );
    }

    /**
     * Create a tax group
     *
     * @param array $fields
     * @return array $response
     */
    public function createTaxGroup( $fields )
    {
        $group = new TaxGroup;
        $group->name = $fields[ 'name' ];
        $group->description = $fields[ 'description' ];
        $group->author = Auth::id();
        $group->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The tax group has been correctly saved.' ),
            'data'      =>  compact( 'group' ),
        ];
    }

    /**
     * creates a tax using provided details
     */
    public function createTax( $fields )
    {
        $tax = new Tax;
        $tax->name = $fields[ 'name' ];
        $tax->rate = $fields[ 'rate' ];
        $tax->tax_group_id = $fields[ 'tax_group_id' ];
        $tax->description = $fields[ 'description' ];
        $tax->author = Auth::id();
        $tax->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The tax has been correctly created.' ),
            'data'      =>  compact( 'tax' ),
        ];
    }

    /**
     * Create a tax using the provided details
     *
     * @param array tax fields
     *
     * @deprecated
     *
     * @return Tax
     */
    public function create( $fields )
    {
        /**
         * Check if the parent tax exists
         */
        if ( isset( $fields[ 'parent_id' ] ) ) {
            $parent = Tax::find( $fields[ 'parent_id' ] );

            $this->__checkTaxParentExists( $parent );
            $this->__checkTaxParentOnCreation( $parent );
            $this->__checkIfNotGroupedTax( $fields );
        }

        /**
         * @todo check circular hierarchy
         */
        $tax = new Tax;

        foreach ( $fields as $field => $value ) {
            $tax->$field = $value;
        }

        $tax->author = Auth::id();
        $tax->save();

        return $tax;
    }

    /**
     * Update a provided tax using
     * the identifier and the data
     *
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
            $parent = Tax::find( $fields[ 'parent_id' ] );

            $this->__checkTaxParentExists( $parent );
            $this->__checkTaxParentOnCreation( $parent );
            $this->__checkTaxParentOnModification( $parent, $id );
            $this->__checkIfNotGroupedTax( $fields );
        }

        /**
         * @todo check circular hierarchy
         */
        $tax = $this->get( $id );

        foreach ( $fields as $field => $value ) {
            $tax->$field = $value;
        }

        $tax->author = Auth::id();
        $tax->save();

        return $tax;
    }

    /**
     * Retreive the tax value for a specific
     * amount using a determined tax group id on which the calculation is made
     *
     * @param string $tax_type might be "inclusive" or "exclusive".
     * @param int $tax_group_id is the tax group id on which the calculation is made
     * @param float $price the amount used for the calculation
     * @return float calculated value
     */
    public function getComputedTaxGroupValue( $tax_type, $tax_group_id, $price )
    {
        $taxGroup = TaxGroup::find( $tax_group_id );
        $taxValue = 0;

        if ( $taxGroup instanceof TaxGroup ) {
            $taxValue = $taxGroup->taxes
                ->map( function( $tax ) use ( $tax_type, $price ) {
                    $taxValue = $this->getVatValue(
                        $tax_type,
                        floatval( $tax[ 'rate' ] ),
                        $price
                    );

                    return $taxValue;
                })
                ->sum();
        }

        return $taxValue;
    }

    /**
     * compute the tax added to a
     * product using a defined tax group id and type
     *
     * @param ProductUnitQuantity
     * @param int $tax_group_id the tax group on which the calculation is made
     * @param string $tax_type might be "inclusive" or "exclusive"
     * @return void
     */
    public function computeTax( ProductUnitQuantity $product, $tax_group_id, $tax_type = null )
    {
        $taxGroup = TaxGroup::find( $tax_group_id );

        $product->sale_price = $this->currency->define( $product->sale_price_edit )->getRaw();
        $product->net_sale_price = $this->currency->define( $product->sale_price_edit )->getRaw();
        $product->gross_sale_price = $this->currency->define( $product->sale_price_edit )->getRaw();
        $product->wholesale_price = $this->currency->define( $product->wholesale_price_edit )->getRaw();
        $product->net_wholesale_price = $this->currency->define( $product->wholesale_price_edit )->getRaw();
        $product->gross_wholesale_price = $this->currency->define( $product->wholesale_price_edit )->getRaw();
        $product->sale_price_tax = 0;
        $product->wholesale_price_tax = 0;

        /**
         * calculate the taxes wether they are all
         * inclusive or exclusive for the sale_price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxRate = $taxGroup->taxes
                ->map( function( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                })
                ->sum();

            if ( ( $tax_type ?? $product->tax_type) === 'inclusive' ) {
                $product->gross_sale_price = ( floatval( $product->sale_price_edit ) );
                $product->sale_price_tax = ( floatval( $this->getVatValue( 'inclusive', $taxRate, $product->sale_price_edit ) ) );
                $product->net_sale_price = $this->getNetPrice(
                    'inclusive',
                    $taxRate,
                    $product->sale_price_edit
                );
                $product->sale_price = $product->gross_sale_price;
            } else {
                $product->net_sale_price = floatval( $product->sale_price_edit );
                $product->sale_price_tax = ( floatval( $this->getVatValue( 'exclusive', $taxRate, $product->sale_price_edit ) ) );
                $product->gross_sale_price = $this->getGrossPrice(
                    'exclusive',
                    $taxRate,
                    $product->sale_price_edit
                );
                $product->sale_price = $product->gross_sale_price;
            }
        }

        /**
         * calculate the taxes wether they are all
         * inclusive or exclusive for the wholesale price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxRate = $taxGroup->taxes
                ->map( function( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                })
                ->sum();

            if ( ( $tax_type ?? $product->tax_type ) === 'inclusive' ) {
                $product->wholesale_price_tax = ( floatval( $this->getVatValue( 'inclusive', $taxRate, $product->wholesale_price_edit ) ) );
                $product->gross_wholesale_price = $this->currency->define( $product->wholesale_price_edit )->getRaw();
                $product->net_wholesale_price = $this->getNetPrice(
                    'inclusive',
                    $taxRate,
                    $product->wholesale_price_edit
                );
                $product->wholesale_price = $product->gross_wholesale_price;
            } else {
                $product->wholesale_price_tax = ( floatval( $this->getVatValue( 'exclusive', $taxRate, $product->wholesale_price_edit ) ) );
                $product->net_wholesale_price = $this->currency->define( $product->wholesale_price_edit )->getRaw();
                $product->gross_wholesale_price = $this->getGrossPrice(
                    'exclusive',
                    $taxRate,
                    $product->wholesale_price_edit
                );
                $product->wholesale_price = $product->gross_wholesale_price;
            }
        }

        $product->save();
    }

    /**
     * Compute tax for a provided unit group
     *
     * @param string $type inclusive or exclusive
     * @param TaxGroup $group
     * @param float|int $value
     * @return float
     *
     * @deprecated
     */
    public function getTaxGroupComputedValue( $type, TaxGroup $group, $value )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        switch ( $type ) {
            case 'inclusive': return $this->getNetPrice( $type, $rate, $value );
            case 'exclusive': return $this->getGrossPrice( $type, $rate, $value );
        }

        return 0;
    }

    public function computeNetAndGrossPrice( $orderProduct )
    {
        /**
         * let's compute the discount
         * for that specific product
         * before computing taxes
         */
        $discount = (float) 0;

        if ( $orderProduct->discount_type === 'percentage' ) {
            $discount = $this->getPercentageOf(
                value: $orderProduct->unit_price * $orderProduct->quantity,
                rate: $orderProduct->discount_percentage,
            );
        } elseif ( $orderProduct->discount_type === 'flat' ) {
            /**
             * @todo not exactly correct.  The discount should be defined per
             * price type on the frontend.
             */
            $discount = $orderProduct->discount;
        }

        /**
         * Let's now compute the taxes
         */
        $taxGroup = TaxGroup::find( $orderProduct->tax_group_id );
        $grossPriceEnabled = ns()->option->get( 'ns_pos_gross_price_used', 'no' ) === 'yes';

        /**
         * if the tax group is not defined,
         * then probably it's not assigned to the product.
         */
        if ( $taxGroup instanceof TaxGroup ) {
            if ( $grossPriceEnabled ) {
                $net_price = $this->getNetPriceFromGrossPriceUsingGroup( $orderProduct->unit_price - $discount, $taxGroup );

                $orderProduct->net_price = $net_price;
                $orderProduct->gross_price = $orderProduct->unit_price - $discount;
                $orderProduct->tax_value = ( $orderProduct->gross_price - $orderProduct->net_price ) * $orderProduct->quantity;
            } else {
                $gross_price = $this->getGrossPriceFromNetPriceUsingGroup( $orderProduct->unit_price - $discount, $taxGroup );

                $orderProduct->net_price = $orderProduct->unit_price - $discount;
                $orderProduct->gross_price = $gross_price;
                $orderProduct->tax_value = ( $orderProduct->gross_price - $orderProduct->net_price ) * $orderProduct->quantity;
            }
        }

        $orderProduct->discount = $discount;

        $orderProduct->total_gross_price = ns()->currency
            ->fresh( $orderProduct->gross_price )
            ->multiplyBy( $orderProduct->quantity )
            ->get();

        $orderProduct->total_price = ns()->currency
            ->fresh( $orderProduct->unit_price )
            ->multiplyBy( $orderProduct->quantity )
            ->subtractBy( $discount )
            ->getFullRaw();

        $orderProduct->total_net_price = ns()->currency
            ->fresh( $orderProduct->net_price )
            ->multiplyBy( $orderProduct->quantity )
            ->get();

        return $orderProduct;
    }

    /**
     * Retuns the gross price from a net price
     * using a defined rate
     *
     * @param float $gross_price
     * @param float $rate
     * @return float
     */
    public function getGrossPriceFromNetPrice( $net_price, $rate )
    {
        return $net_price * ( 100 + $rate ) / 100;
    }

    /**
     * Retuns the net price from a gross price
     * using a defined rate
     *
     * @param float $gross_price
     * @param float $rate
     * @return float
     */
    public function getNetPriceFromGrossPrice( $gross_price, $rate )
    {
        return $gross_price * 100 / ( 100 + $rate );
    }

    /**
     * Compute the gross price from net price
     * using the tax group rate
     *
     * @param float $net_price
     * @param TaxGroup $group
     * @return float
     */
    public function getGrossPriceFromNetPriceUsingGroup( $net_price, TaxGroup $group )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getGrossPriceFromNetPrice( $net_price, $rate );
    }

    /**
     * Computes the net price using the gross
     * price along with a TaxGroup rate
     *
     * @param float $gross_price
     * @param TaxGroup $group
     * @return float
     */
    public function getNetPriceFromGrossPriceUsingGroup( $gross_price, TaxGroup $group )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getNetPriceFromGrossPrice( $gross_price, $rate );
    }

    /**
     * Will only return the net price
     * using a tax group provided
     *
     * @param string $type
     * @param TaxGroup $group
     * @param $value
     * @return float
     */
    public function getTaxGroupNetPrice( $type, TaxGroup $group, $value )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getNetPrice( $type, $rate, $value );
    }

    /**
     * Will only return the gross price
     * using a tax group provided
     *
     * @param string $type
     * @param TaxGroup $group
     * @param $value
     * @return float
     */
    public function getTaxGroupGrossPrice( $type, TaxGroup $group, $value )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getGrossPrice( $type, $rate, $value );
    }

    /**
     * Compute the net price using a rate
     * and a tax type provided
     *
     * @param string $type
     * @param float $rate
     * @param float $value
     * @return float
     */
    public function getNetPrice( $type, float $rate, float $value )
    {
        if ( $type === 'inclusive' ) {
            return $this->currency->getRaw( ( $value / ( $rate + 100 ) ) * 100 );
        }

        return $value;
    }

    /**
     * Compute the gross price using a rate
     * and a tax type provided
     *
     * @param string $type
     * @param float $rate
     * @param float $value
     * @return float
     */
    public function getGrossPrice( $type, float $rate, float $value )
    {
        if ( $type === 'exclusive' ) {
            return $this->currency->getRaw( ( $value / 100 ) * ( $rate + 100 ) );
        }

        return $value;
    }

    /**
     * @deprecated
     */
    public function getComputedTaxValue( $type, float $rate, float $value )
    {
        if ( $type === 'inclusive' ) {
            return $this->currency->getRaw( ( $value / ( $rate + 100 ) ) * 100 );
        } elseif ( $type === 'exclusive' ) {
            return $this->currency->getRaw( ( $value / 100 ) * ( $rate + 100 ) );
        }

        return $value;
    }

    public function getVatValue( $type, float $rate, float $value )
    {
        if ( $type === 'inclusive' ) {
            return $value - $this->getNetPrice( $type, $rate, $value );
        } elseif ( $type === 'exclusive' ) {
            return $this->getGrossPrice( $type, $rate, $value ) - $value;
        }
    }

    /**
     * get the tax value from an amount calculated
     * over a provided tax group
     *
     * @param string type
     * @param TaxGroup
     * @param float $value
     * @return float
     */
    public function getTaxGroupVatValue( $type, TaxGroup $group, float $value )
    {
        if ( $type === 'inclusive' ) {
            return $value - $this->getTaxGroupNetPrice( $type, $group, $value );
        } elseif ( $type === 'exclusive' ) {
            return $this->getTaxGroupGrossPrice( $type, $group, $value ) - $value;
        }
    }

    /**
     * calculate a percentage of a provided
     * value using a defined rate
     *
     * @param float value
     * @param float rate
     * @return float
     */
    public function getPercentageOf( $value, $rate )
    {
        return $this->currency->fresh( $value )
            ->multipliedBy( $rate )
            ->dividedBy( 100 )
            ->getFullRaw();
    }

    /**
     * Update the product tax using
     * the provided tax/product combinaison
     *
     * @param Product product itself
     * @param Tax tax itself
     * @return array
     *
     * @deprecated
     */
    public function saveProductTax( Product $product, Tax $tax )
    {
        $taxValue = $this->currency->value( $product->sale_price )
            ->multiplyBy( $tax->rate )
            ->divideBy( 100 )
            ->get();

        $product->tax_value = $taxValue;
        $product->save();

        /**
         * let's update or add a ProductTax entry.
         * We assume there can only be unique
         * tax_id & product_id combinaison
         */
        $productTax = ProductTax::findMatch([
            'product_id'    =>  $product->id,
            'tax_id'        =>  $tax->id,
        ])->first();

        if ( $productTax instanceof ProductTax ) {
            $productTax->name = $tax->name; // in case it has changed
            $productTax->rate = $tax->rate;
            $productTax->value = $taxValue;
            $productTax->author = Auth::id();
            $productTax->save();
        } else {
            $productTax = new ProductTax;
            $productTax->product_id = $product->id;
            $productTax->tax_id = $tax->id;
            $productTax->name = $tax->name; // in case it has changed
            $productTax->rate = $tax->rate;
            $productTax->value = $taxValue;
            $productTax->author = Auth::id();
            $productTax->save();
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product tax has been saved.' ),
            'data'      =>  [
                'tax'   =>  $productTax,
            ],
        ];
    }

    /**
     * delete a specific tax using
     * a provided identifier
     *
     * @param int tax id
     * @return array|Exception response of the operation
     */
    public function delete( $id )
    {
        $tax = $this->get( $id );
        $tax->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The tax has been successfully deleted.' ),
        ];
    }
}
