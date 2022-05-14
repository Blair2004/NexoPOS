<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nexopos_products_subitems', function (Blueprint $table) {
            $table->id();
            $table->integer( 'product_id' );
            $table->integer( 'unit_id' );
            $table->integer( 'unit_quantity_id' );
            $table->float( 'quantity' )->default(0);
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
        Schema::dropIfExists('nexopos_products_subitems');
    }
};
