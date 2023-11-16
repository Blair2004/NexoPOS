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
        Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_before' ) ) {
                $table->float( 'balance_before', 18, 5 )->change();
            }
            if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_after' ) ) {
                $table->float( 'balance_after', 18, 5 )->change();
            }
        });

        Schema::table( 'nexopos_users', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_users', 'total_sales' ) ) {
                $table->float( 'total_sales', 18, 5 )->change();
            }
        });

        Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'support_instalments' ) ) {
                $table->boolean( 'support_instalments' )->default(true);
            }
        });
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
