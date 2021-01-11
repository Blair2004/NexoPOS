<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Nov5AddNewFieldsOnNexoposCustomersAccountHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::table( 'nexopos_customers_account_history', function( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_customers_account_history', 'order_id' ) ) {
                    $table->integer( 'order_id' )->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::table( 'nexopos_customers_account_history', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_customers_account_history', 'order_id' ) ) {
                    $table->dropColumn( 'order_id' );
                }
            });
        }
    }
}
