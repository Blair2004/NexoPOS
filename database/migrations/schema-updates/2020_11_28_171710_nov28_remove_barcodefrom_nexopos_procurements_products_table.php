<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Nov28RemoveBarcodefromNexoposProcurementsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'barcode' ) ) {
                    $table->dropColumn( 'barcode' );
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
        //
    }
}
