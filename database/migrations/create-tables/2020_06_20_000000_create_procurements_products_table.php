<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
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
            Schema::createIfMissing( 'nexopos_procurements_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->float( 'gross_purchase_price', 18, 5 )->default(0);
                $table->float( 'net_purchase_price', 18, 5 )->default(0);
                $table->integer( 'procurement_id' );
                $table->integer( 'product_id' );
                $table->float( 'purchase_price', 18, 5 )->default(0);
                $table->float( 'quantity', 18, 5 );
                $table->float( 'available_quantity', 18, 5 );
                $table->integer( 'tax_group_id' );
                $table->string( 'barcode' )->nullable();
                $table->datetime( 'expiration_date' )->nullable();
                $table->string( 'tax_type' ); // inclusive or exclusive;
                $table->float( 'tax_value', 18, 5 )->default(0);
                $table->float( 'total_purchase_price', 18, 5 )->default(0);
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
            Schema::dropIfExists( 'nexopos_procurements_products' );
        }
    }
}

