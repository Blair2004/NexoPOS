<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //  products_flat_vat
        // products_variable_vat
        if ( ns()->option->get( 'ns_pos_vat' ) === 'products_flat_vat' ) {
            ns()->option->set( 'ns_pos_vat', 'flat_vat' );
            ns()->option->set( 'ns_pos_price_with_tax', 'yes' );
        }

        if ( ns()->option->get( 'ns_pos_vat' ) === 'products_variable_vat' ) {
            ns()->option->set( 'ns_pos_vat', 'variable_vat' );
            ns()->option->set( 'ns_pos_price_with_tax', 'yes' );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
