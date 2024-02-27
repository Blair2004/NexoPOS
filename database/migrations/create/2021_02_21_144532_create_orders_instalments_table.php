<?php

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
        Schema::createIfMissing( 'nexopos_orders_instalments', function ( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->float( 'amount', 18, 5 )->default( 0 );
            $table->integer( 'order_id' )->nullable();
            $table->boolean( 'paid' )->default( false );
            $table->integer( 'payment_id' )->nullable();
            $table->datetime( 'date' );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_orders_instalments' );
    }
};
