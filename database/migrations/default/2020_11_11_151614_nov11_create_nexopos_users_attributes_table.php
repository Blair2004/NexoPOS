<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Nov11CreateNexoposUsersAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'nexopos_users_attributes', function( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->integer( 'user_id' );
            $table->string( 'first_name' )->nullable();
            $table->string( 'second_name' )->nullable();
            $table->string( 'phone' )->nullable();
            $table->string( 'avatar_link' )->nullable();
            $table->string( 'theme' )->nullable();
            $table->string( 'language' )->nullable();
            $table->string( 'address_1' )->nullable();
            $table->string( 'address_2' )->nullable();
            $table->string( 'country' )->nullable();
            $table->string( 'city' )->nullable();
            $table->string( 'gender' )->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_users_attributes' );
    }
}
