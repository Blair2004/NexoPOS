<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateProductsTableMarch10 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'accurate_tracking' ) ) {
                $table->boolean( 'accurate_tracking' )->default( false );
            }
        });

        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements_products', 'available_quantity' ) ) {
                $table->float( 'available_quantity', 8, 5 )->default(0);
            }
        });

        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'expiration_date' ) ) {
                $table->dropColumn( 'expiration_date' );
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products', 'accurate_tracking' ) ) {
                $table->dropColumn( 'accurate_tracking' );
            }
        });

        Schema::table( 'nexopos_procurements_prodcuts', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_prodcuts', 'available_quantity' ) ) {
                $table->dropColumn( 'available_quantity' );
            }
        });

        Schema::table( 'nexopos_procurements_prodcuts', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_prodcuts', 'expiration_date' ) ) {
                $table->dropColumn( 'expiration_date' );
            }
        });
    }
}
