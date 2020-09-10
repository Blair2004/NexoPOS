<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcurementsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::create( 'nexopos_procurements_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->float( 'gross_purchase_price' )->default(0);
                $table->float( 'net_purchase_price' )->default(0);
                $table->integer( 'procurement_id' );
                $table->integer( 'product_id' );
                $table->float( 'purchase_price' )->default(0);
                $table->float( 'quantity' );
                $table->integer( 'tax_group_id' );
                $table->string( 'tax_type' ); // inclusive or exclusive;
                $table->float( 'tax_value' )->default(0);
                $table->float( 'total_purchase_price' )->default(0);
                $table->integer( 'unit_id' );
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::drop( 'nexopos_procurements_products' );
        }
    }
}

