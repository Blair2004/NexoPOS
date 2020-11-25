<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Nov17AddFieldsToNexoposOrdersProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_refunds' ), function( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->integer( 'order_id' );
            $table->integer( 'author' );
            $table->float( 'total' );
            $table->string( 'payment_method' );
            $table->timestamps();
        });

        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products_refunds' ), function (Blueprint $table) {
            $table->bigIncrements( 'id' );
            $table->integer( 'order_id' );
            $table->integer( 'order_refund_id' );
            $table->integer( 'order_product_id' );
            $table->integer( 'unit_id' );
            $table->integer( 'product_id' );
            $table->float( 'unit_price' );
            $table->float( 'quantity' );
            $table->float( 'total_price' );
            $table->string( 'condition' ); // either unspoiled, damaged
            $table->text( 'description' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products_refunds' ) );
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_refunds' ) );
    }
}
