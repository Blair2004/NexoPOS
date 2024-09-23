<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table( 'nexopos_orders_changes', function ( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->integer( 'order_id' );
            $table->string( 'identifier' );
            $table->enum( 'type', [ 'voucher', 'change' ] )->default( 'voucher' );
            $table->float( 'value' );   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_orders_payments', function ( Blueprint $table ) {
            $table->dropColumn( 'type' );
        } );
    }
};
