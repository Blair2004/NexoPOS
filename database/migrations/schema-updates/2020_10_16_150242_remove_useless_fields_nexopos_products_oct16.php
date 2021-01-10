<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class RemoveUselessFieldsNexoposProductsOct16 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach([ 
            'wholesale_price_edit',
            'sale_price_edit',
            'sale_price',
            'sale_price_edit',
            'excl_tax_sale_price',
            'incl_tax_sale_price',
            'wholesale_price',
            'wholesale_price_edit',
            'incl_tax_wholesale_price',
            'excl_tax_wholesale_price',
        ] as $field ) {
            Schema::table( 'nexopos_products', function( Blueprint $table ) use( $field ) {
                if ( Schema::hasColumn( 'nexopos_products', $field ) ) {
                    $table->dropColumn( $field );
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach([ 
            'wholesale_price_edit',
            'sale_price_edit',
            'sale_price',
            'sale_price_edit',
            'excl_tax_sale_price',
            'incl_tax_sale_price',
            'wholesale_price',
            'wholesale_price_edit',
            'incl_tax_wholesale_price',
            'excl_tax_wholesale_price',
        ] as $field ) {
            if ( Schema::hasTable( 'nexopos_products' ) ) {
                Schema::table( 'nexopos_products', function( Blueprint $table ) use( $field ) {
                    if ( ! Schema::hasColumn( 'nexopos_products', $field ) ) {
                        $table->float( $field )->default(0);
                    }
                });
            }
        }
    }
}
