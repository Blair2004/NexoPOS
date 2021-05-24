<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAttributesMay9 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_users_attributes', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_users_attributes', 'language' ) ) {
                $table->string( 'language' )->nullable();
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
            if ( Schema::hasColumn( 'nexopos_users_attributes', 'language' ) ) {
                $table->dropColumn( 'language' );
            }
        });
    }
}
