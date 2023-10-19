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
        Schema::table( 'nexopos_customers_addresses', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_customers_addresses', 'email' ) ) {
                $table->string( 'email' )->nullable();
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
        Schema::table( 'nexopos_customers_addresses', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nxopos_customers_addresses', 'email' ) ) {
                $table->dropColumn( 'email' );
            }
        });
    }
};
