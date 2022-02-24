<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateCustomerTableJan1122 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_customers', function( Blueprint $table ) {
            $keyExists = DB::select(
                DB::raw(
                    'SHOW KEYS
                    FROM ' . DB::getTablePrefix() . Hook::filter( 'ns-model-table', 'nexopos_customers' ) .' 
                    WHERE Key_name=\'email\''
                )
            );

            if ( Schema::hasColumn( 'nexopos_customers', 'email' ) && ! empty( $keyExists ) ) {
                $table->dropUnique( 'email' );
            } 

            if ( ! Schema::hasColumn( 'nexopos_customers', 'credit_limit_amount' ) ) {
                $table->float( 'credit_limit_amount' )->default(0)->nullable();
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
        Schema::table( 'nexopos_customers', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_customers', 'credit_limit_amount' ) ) {
                $table->dropColumn( 'credit_limit_amount' );
            }
        });
    }
}
