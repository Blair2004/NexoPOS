<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAttributeJan2222 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_users_attributes', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_users_attributes', 'theme' ) ) {
                $table->string( 'theme' )->default( 'light' );
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
        Schema::table( 'nexopos_users_attributes', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_users_attributes', 'theme' ) ) {
                $table->dropColumn( 'theme' );
            }
        });
    }
}
