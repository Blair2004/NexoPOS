<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAttributesMar18 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_users_attributes', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_users_attributes', 'avatar_link' ) ) {
                $table->string( 'avatar_link' )->nullable();
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
            if ( Schema::hasColumn( 'nexopos_users_attributes', 'avatar_link' ) ) {
                $table->dropColumn( 'avatar_link' );
            }
        });
    }
}
