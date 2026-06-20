<?php

/**
 * Table Migration
 **/

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::createIfMissing( 'nexopos_procurements_products', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->decimal( 'gross_purchase_price', 18, 5 )->default( 0 );
                $table->decimal( 'net_purchase_price', 18, 5 )->default( 0 );
                $table->integer( 'procurement_id' );
                $table->integer( 'product_id' );
                $table->decimal( 'purchase_price', 18, 5 )->default( 0 );
                $table->float( 'quantity', 18 );
                $table->float( 'available_quantity', 18 )->default( 0 );
                $table->integer( 'tax_group_id' )->nullable();
                $table->string( 'barcode' )->nullable();
                $table->datetime( 'expiration_date' )->nullable();
                $table->string( 'tax_type' ); // inclusive or exclusive;
                $table->decimal( 'tax_value', 18, 5 )->default( 0 );
                $table->decimal( 'total_purchase_price', 18, 5 )->default( 0 );
                $table->integer( 'unit_id' );
                $table->integer( 'convert_unit_id' )->nullable();
                $table->integer( 'author_id' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::dropIfExists( 'nexopos_procurements_products' );
        }
    }
};
