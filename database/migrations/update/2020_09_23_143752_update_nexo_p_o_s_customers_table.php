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
        Schema::table( 'nexopos_customers', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_customers', 'purchases_amount' ) ) {
                $table->float( 'purchases_amount' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_customers', 'owed_amount' ) ) {
                $table->float( 'owed_amount' )->default(0);
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
        Schema::table( 'nexopos_customers', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_customers', 'purchases_amount' ) ) {
                $table->dropColumn( 'purchases_amount' );
            }

            if ( Schema::hasColumn( 'nexopos_customers', 'owe_amount' ) ) {
                $table->dropColumn( 'owe_amount' );
            }
        });
    }
};
