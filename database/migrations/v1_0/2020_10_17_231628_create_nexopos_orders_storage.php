<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNexoposOrdersStorage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_storage' ), function (Blueprint $table) {
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
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_storage' ) );
    }
}
