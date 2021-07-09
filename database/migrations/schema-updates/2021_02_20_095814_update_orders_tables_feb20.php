<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateOrdersTablesFeb20 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders', 'expected_payment_date' ) ) {
                $table->renameColumn( 'expected_payment_date', 'final_payment_date' );
            }
            if ( Schema::hasColumn( 'nexopos_orders', 'total_installments' ) ) {
                $table->renameColumn( 'total_installments', 'total_instalments' );
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
}
