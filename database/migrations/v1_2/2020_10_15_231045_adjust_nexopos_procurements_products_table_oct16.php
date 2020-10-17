<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustNexoposProcurementsProductsTableOct16 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements_products', 'expiration_date' ) ) {
                $table->datetime( 'expiration_date' )->nullable();
            }

            if ( ! Schema::hasColumn( 'nexopos_procurements_products', 'barcode' ) ) {
                $table->string( 'barcode' );
            }
        });

        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products', 'expiration' ) ) {
                $table->dropColumn( 'expiration' );
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
        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_products', 'expiration_date' ) ) {
                $table->dropColumn( 'expiration_date' );
            }

            if ( Schema::hasColumn( 'nexopos_procurements_products', 'barcode' ) ) {
                $table->dropColumn( 'barcode' );
            }
        });

        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'expiration' ) ) {
                $table->datetime( 'expiration' )->nullable();
            }
        });
    }
}
