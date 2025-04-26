<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_orders_settings' ) ) {
            Schema::create( 'nexopos_orders_settings', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'order_id' );
                $table->string( 'key' );
                $table->text( 'value' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_orders_settings' );
    }
};
