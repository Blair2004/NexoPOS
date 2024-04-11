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
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'payment_id' ) ) {
                    $table->integer( 'payment_id' )->nullable();
                }

                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'payment_type_id' ) ) {
                    $table->integer( 'payment_type_id' )->default( 0 );
                }

                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'order_id' ) ) {
                    $table->integer( 'order_id' )->nullable();
                }
            } );
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
};
