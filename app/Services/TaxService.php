<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\OrderProduct;
use App\Models\Product;
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
            throw new NotFoundException( __( 'Unable to find the requested tax using the provided identifier.' ) );
        }

        return $tax;
    }

    /**
     * That will return the first
     * tax using a specific name
     *
     * @param  string $name
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
     * @param  string   $name
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
     * @param  array $fields
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
            'status' => 'success',
            'message' => __( 'The tax group has been correctly saved.' ),
            'data' => compact( 'group' ),
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
            'status' => 'success',
            'message' => __( 'The tax has been correctly created.' ),
            'data' => compact( 'tax' ),
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
     */
    public function getComputedTaxGroupValue( ?string $tax_type, ?int $tax_group_id, float $price )
    {
        $taxGroup = TaxGroup::find( $tax_group_id );

        if ( $taxGroup instanceof TaxGroup ) {
            $summarizedRate = $taxGroup->taxes->sum( 'rate' );

            return $this->getVatValue( $tax_type, $summarizedRate, $price );
        }

        return 0;
    }

    /**
     * Will compute the tax value for each tax rate
     * provided on the $rates and return the global tax value
     * and the tax value for each rates.
     */
    public function getTaxesComputed( string $tax_type, array $rates, float $value ): array
    {
        $response = [];
        $response[ 'value' ] = $value;
        $response[ 'rate' ] = collect( $rates )->sum();
        $response[ 'percentages' ] = collect( $rates )->map( fn( $rate ) => ns()->currency->define(
            ns()->currency->define( $rate )->dividedBy( $response[ 'rate' ] )->toFloat()
        )->multipliedBy( 100 )->toFloat() );

        if ( $tax_type === 'inclusive' ) {
            $response[ 'with-tax' ] = $response[ 'value' ];
            $response[ 'without-tax' ] = $this->getPriceWithoutTax(
                type: $tax_type,
                rate: $response[ 'rate' ],
                value: $value
            );

            $response[ 'tax' ] = $value - $response[ 'without-tax' ];
        } elseif ( $tax_type === 'exclusive' ) {
            $response[ 'without-tax' ] = $response[ 'value' ];
            $response[ 'with-tax' ] = $this->getPriceWithTax(
                type: $tax_type,
                rate: $response[ 'rate' ],
                value: $value
            );

            $response[ 'tax' ] = $response[ 'with-tax' ] - $value;
        } else {
        }

        /**
         * let's now compute the individual values
         */
        $response[ 'percentages' ] = collect( $response[ 'percentages' ] )->map( function ( $percentage ) use ( $value, $response ) {
            $computed = ns()->currency->define(
                ns()->currency->define( $value )->multipliedBy( $percentage )->toFloat()
            )->divideBy( 100 )->toFloat();

            $tax = ns()->currency->define(
                ns()->currency->define( $response[ 'tax' ] )->multipliedBy( $percentage )->toFloat()
            )->dividedBy( 100 )->toFloat();

            return compact( 'computed', 'percentage', 'tax' );
        } )->toArray();

        return $response;
    }

    /**
     * compute the tax added to a
     * product using a defined tax group id and type.
     */
    public function computeTax( ProductUnitQuantity $unitQuantity, ?int $tax_group_id, ?string $tax_type = null ): void
    {
        $taxGroup = TaxGroup::find( $tax_group_id );

        $unitQuantity->sale_price = $this->currency->define( $unitQuantity->sale_price_edit )->toFloat();
        $unitQuantity->sale_price_with_tax = $this->currency->define( $unitQuantity->sale_price_edit )->toFloat();
        $unitQuantity->sale_price_without_tax = $this->currency->define( $unitQuantity->sale_price_edit )->toFloat();
        $unitQuantity->sale_price_tax = 0;

        $unitQuantity->wholesale_price = $this->currency->define( $unitQuantity->wholesale_price_edit )->toFloat();
        $unitQuantity->wholesale_price_with_tax = $this->currency->define( $unitQuantity->wholesale_price_edit )->toFloat();
        $unitQuantity->wholesale_price_without_tax = $this->currency->define( $unitQuantity->wholesale_price_edit )->toFloat();
        $unitQuantity->wholesale_price_tax = 0;

        /**
         * calculate the taxes whether they are all
         * inclusive or exclusive for the sale_price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxRate = $taxGroup->taxes
                ->map( function ( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                } )
                ->sum();

            if ( ( $tax_type ?? $unitQuantity->tax_type ) === 'inclusive' ) {
                $unitQuantity->sale_price_with_tax = ( floatval( $unitQuantity->sale_price_edit ) );
                $unitQuantity->sale_price_without_tax = $this->getPriceWithoutTax(
                    type: 'inclusive',
                    rate: $taxRate,
                    value: $unitQuantity->sale_price_edit
                );
                $unitQuantity->sale_price_tax = ( floatval( $this->getVatValue( 'inclusive', $taxRate, $unitQuantity->sale_price_edit ) ) );
                $unitQuantity->sale_price = $unitQuantity->sale_price_with_tax;
            } else {
                $unitQuantity->sale_price_without_tax = floatval( $unitQuantity->sale_price_edit );
                $unitQuantity->sale_price_with_tax = $this->getPriceWithTax(
                    type: 'exclusive',
                    rate: $taxRate,
                    value: $unitQuantity->sale_price_edit
                );
                $unitQuantity->sale_price_tax = ( floatval( $this->getVatValue( 'exclusive', $taxRate, $unitQuantity->sale_price_edit ) ) );
                $unitQuantity->sale_price = $unitQuantity->sale_price_with_tax;
            }
        }

        /**
         * calculate the taxes whether they are all
         * inclusive or exclusive for the wholesale price
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxRate = $taxGroup->taxes
                ->map( function ( $tax ) {
                    return floatval( $tax[ 'rate' ] );
                } )
                ->sum();

            if ( ( $tax_type ?? $unitQuantity->tax_type ) === 'inclusive' ) {
                $unitQuantity->wholesale_price_tax = ( floatval( $this->getVatValue( 'inclusive', $taxRate, $unitQuantity->wholesale_price_edit ) ) );
                $unitQuantity->wholesale_price_with_tax = $this->currency->define( $unitQuantity->wholesale_price_edit )->toFloat();
                $unitQuantity->wholesale_price_without_tax = $this->getPriceWithoutTax(
                    type: 'inclusive',
                    rate: $taxRate,
                    value: $unitQuantity->wholesale_price_edit
                );
                $unitQuantity->wholesale_price = $unitQuantity->wholesale_price_without_tax;
            } else {
                $unitQuantity->wholesale_price_tax = ( floatval( $this->getVatValue( 'exclusive', $taxRate, $unitQuantity->wholesale_price_edit ) ) );
                $unitQuantity->wholesale_price_without_tax = $this->currency->define( $unitQuantity->wholesale_price_edit )->toFloat();
                $unitQuantity->wholesale_price_with_tax = $this->getPriceWithTax(
                    type: 'exclusive',
                    rate: $taxRate,
                    value: $unitQuantity->wholesale_price_edit
                );
                $unitQuantity->wholesale_price = $unitQuantity->wholesale_price_without_tax;
            }
        }

        $unitQuantity->save();
    }

    /**
     * Compute tax for a provided unit group
     *
     * @param  string    $type  inclusive or exclusive
     * @param  float|int $value
     * @return float
     *
     * @deprecated
     */
    public function getTaxGroupComputedValue( $type, TaxGroup $group, $value )
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        switch ( $type ) {
            case 'inclusive': return $this->getPriceWithTax( $type, $rate, $value );
            case 'exclusive': return $this->getPriceWithoutTax( $type, $rate, $value );
        }

        return 0;
    }

    /**
     * We might not need to perform this if
     * the product already comes with defined tax.
     */
    public function computeOrderProductTaxes( OrderProduct $orderProduct ): OrderProduct
    {
        /**
         * let's load the original product with the tax group
         */
        $orderProduct->load( 'product.tax_group' );

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

        $type = $orderProduct->product instanceof Product ? $orderProduct->product->tax_type : ns()->option->get( 'ns_pos_tax_type' );

        /**
         * if the tax group is not defined,
         * then probably it's not assigned to the product.
         */
        if ( $taxGroup instanceof TaxGroup ) {
            if ( $type === 'exclusive' ) {
                $orderProduct->price_with_tax = $orderProduct->unit_price;
                $orderProduct->price_without_tax = $this->getPriceWithoutTaxUsingGroup(
                    type: 'inclusive',
                    price: $orderProduct->price_with_tax - $discount,
                    group: $taxGroup
                );
            } else {
                $orderProduct->price_without_tax = $orderProduct->unit_price;
                $orderProduct->price_with_tax = $this->getPriceWithTaxUsingGroup(
                    type: 'exclusive',
                    price: $orderProduct->price_without_tax - $discount,
                    group: $taxGroup
                );
            }

            $orderProduct->tax_value = ( $orderProduct->price_with_tax - $orderProduct->price_without_tax ) * $orderProduct->quantity;
        }

        $orderProduct->discount = $discount;

        $orderProduct->total_price_without_tax = ns()->currency
            ->fresh( $orderProduct->price_without_tax )
            ->multiplyBy( $orderProduct->quantity )
            ->get();

        $orderProduct->total_price = ns()->currency
            ->fresh( $orderProduct->unit_price )
            ->multiplyBy( $orderProduct->quantity )
            ->subtractBy( $discount )
            ->toFloat();

        $orderProduct->total_price_with_tax = ns()->currency
            ->fresh( $orderProduct->price_with_tax )
            ->multiplyBy( $orderProduct->quantity )
            ->get();

        return $orderProduct;
    }

    /**
     * Compute the gross price from net price
     * using the tax group rate.
     */
    public function getPriceWithoutTaxUsingGroup( string $type, TaxGroup $group, $price ): float
    {
        $rate = $group->taxes->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getPriceWithoutTax( $type, $rate, $price );
    }

    /**
     * Computes the net price using the gross
     * price along with a TaxGroup rate.
     */
    public function getPriceWithTaxUsingGroup( string $type, TaxGroup $group, $price ): float
    {
        $rate = $group->taxes()->get()->map( fn( $tax ) => $tax->rate )->sum();

        return $this->getPriceWithTax( $type, $rate, $price );
    }

    /**
     * Compute the net price using a rate
     * and a tax type provided.
     */
    public function getPriceWithoutTax( string $type, float $rate, float $value ): float
    {
        if ( $type === 'inclusive' ) {
            return $this->currency->define(
                $this->currency->define( $value )->dividedBy(
                    $this->currency->define( $rate )->additionateBy( 100 )->toFloat()
                )->toFloat()
            )
                ->multipliedBy( 100 )
                ->toFloat();
        }

        return $value;
    }

    /**
     * Compute the gross price using a rate
     * and a tax type provided.
     */
    public function getPriceWithTax( string $type, float $rate, float $value ): float
    {
        if ( $type === 'exclusive' ) {
            return $this->currency->define(
                $this->currency->define( $value )->dividedBy( 100 )->toFloat()
            )->multipliedBy(
                $this->currency->define( $rate )->additionateBy( 100 )->toFloat()
            )->toFloat();
        }

        return $value;
    }

    /**
     * Computes the vat value for a defined amount.
     */
    public function getVatValue( ?string $type, float $rate, float $value ): float
    {
        if ( $type === 'inclusive' ) {
            return $this->currency->define( $value )->subtractBy( $this->getPriceWithoutTax( $type, $rate, $value ) )->toFloat();
        } elseif ( $type === 'exclusive' ) {
            return $this->currency->define( $this->getPriceWithTax( $type, $rate, $value ) )->subtractBy( $value )->toFloat();
        }

        return 0;
    }

    /**
     * get the tax value from an amount calculated
     * over a provided tax group.
     */
    public function getTaxGroupVatValue( ?string $type, TaxGroup $group, float $value ): float
    {
        if ( $type === 'inclusive' ) {
            return $this->currency->define( $value )->subtractBy( $this->getPriceWithTaxUsingGroup( $type, $group, $value ) )->toFloat();
        } elseif ( $type === 'exclusive' ) {
            return $this->currency->define( $this->getPriceWithoutTaxUsingGroup( $type, $group, $value ) )->subtractBy( $value )->toFloat();
        }

        return 0;
    }

    /**
     * calculate a percentage of a provided
     * value using a defined rate
     */
    public function getPercentageOf( float $value, float $rate ): float
    {
        return $this->currency->fresh( $value )
            ->multipliedBy( $rate )
            ->dividedBy( 100 )
            ->toFloat();
    }

    /**
     * delete a specific tax using
     * a provided identifier
     *
     * @throws NotFoundException
     */
    public function delete( int $id ): array
    {
        $tax = $this->get( $id );
        $tax->delete();

        return [
            'status' => 'success',
            'message' => __( 'The tax has been successfully deleted.' ),
        ];
    }
}
