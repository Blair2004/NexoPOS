<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToNexoposUsersTableNov3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_users', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_users', 'total_sales_count' ) ) {
                $table->float( 'total_sales_count' )->default(0);
            }

            if ( ! Schema::hasColumn( 'nexopos_users', 'total_sales' ) ) {
                $table->float( 'total_sales' )->default(0);
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
        Schema::table('nexopos_users', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_users', 'total_sales_count' ) ) {
                $table->dropColumn( 'total_sales_count' );
            }

            if ( Schema::hasColumn( 'nexopos_users', 'total_sales' ) ) {
                $table->dropColumn( 'total_sales' );
            }
        });
    }
}
