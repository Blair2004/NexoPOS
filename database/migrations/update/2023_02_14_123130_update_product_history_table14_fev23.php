<?php

use App\Classes\Schema;
use App\Models\Migration as ModelsMigration;
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
        Schema::table( 'nexopos_products_histories', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_histories', 'order_product_id' ) ) {
                $table->integer( 'order_product_id' )->nullable();
            }
        } );

        ModelsMigration::truncate();
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
};
