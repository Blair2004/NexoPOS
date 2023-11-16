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
        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::table( 'nexopos_customers', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_customers', 'birth_date' ) ) {
                    $table->datetime( 'birth_date' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::table( 'nexopos_customers', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_customers', 'birth_date' ) ) {
                    $table->dropColumn( 'birth_date' );
                }
            });
        }
    }
};
