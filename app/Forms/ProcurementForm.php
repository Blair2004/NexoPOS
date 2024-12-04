<?php

namespace App\Forms;

use App\Classes\Hook;
use App\Models\Procurement;
use App\Models\Product;
use App\Services\SettingsPage;

class ProcurementForm extends SettingsPage
{
    const IDENTIFIER = 'ns.procurement';

    public $form;

    public function __construct()
    {
        if ( ! empty( request()->route( 'identifier' ) ) ) {
            $procurement = Procurement::with( 'products' )
                ->with( 'provider' )
                ->find( request()->route( 'identifier' ) );
        }

        $this->form = Hook::filter( 'ns-procurement-form', [
            'main' => [
                'name' => 'name',
                'type' => 'text',
                'value' => $procurement->name ?? '',
                'label' => __( 'Procurement Name' ),
                'description' => __( 'Provide a name that will help to identify the procurement.' ),
            ],
            'columns' => Hook::filter( 'ns-procurement-columns', [
                'name' => [
                    'label' => __( 'Name' ),
                    'type' => 'name',
                ],
                'purchase_price_edit' => [
                    'label' => __( 'Unit Price' ),
                    'type' => 'text',
                ],
                'tax_value' => [
                    'label' => __( 'Tax Value' ),
                    'type' => 'currency',
                ],
                'quantity' => [
                    'label' => __( 'Quantity' ),
                    'type' => 'text',
                ],
                'total_purchase_price' => [
                    'label' => __( 'Total Price' ),
                    'type' => 'currency',
                ],
            ] ),
            'products' => isset( $procurement ) ? $procurement->products->map( function ( $_product ) {
                $product = Product::findOrFail( $_product->product_id );
                $product->load( 'unit_quantities.unit' )->get();

                $_product->procurement = array_merge( $_product->toArray(), [
                    '$invalid' => false,
                    'purchase_price_edit' => $_product->purchase_price,
                ] );

                $_product->unit_quantities = $product->unit_quantities;

                return $_product;
            } ) : [],
            'tabs' => [
                'general' => include ( dirname( __FILE__ ) . '/procurement/general.php' ),
            ],
        ] );
    }
}
