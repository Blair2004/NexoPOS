<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTendooUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('nexopos_users');
        Schema::create('nexopos_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string( 'username' );
            $table->boolean( 'active' )->default( false );
            $table->integer( 'role_id' )->nullable();
            $table->integer( 'author' )->nullable(); // the first user is created by him self
            $table->string( 'email' )->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nexopos_users');
    }
}
