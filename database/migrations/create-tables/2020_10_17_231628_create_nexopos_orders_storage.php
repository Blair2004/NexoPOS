<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;;

class CreateNexoposOrdersStorage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_orders_storage', function (Blueprint $table) {
            $table->id();
            $table->integer( 'product_id' )->nullable();
            $table->integer( 'unit_quantity_id' )->nullable();
            $table->integer( 'unit_id' )->nullable();
            $table->integer( 'quantity' )->nullable();
            $table->string( 'session_identifier' );
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
        Schema::dropIfExists( 'nexopos_orders_storage' );
    }
}
