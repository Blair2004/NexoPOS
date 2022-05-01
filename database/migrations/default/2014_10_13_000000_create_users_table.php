<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Determine wether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_users' ) ) {
            Schema::create('nexopos_users', function (Blueprint $table) {
                $table->increments('id');
                $table->string( 'username' );
                $table->boolean( 'active' )->default( false );
                $table->integer( 'author' )->nullable(); // the first user is created by him self
                $table->string( 'email' )->unique();
                $table->string('password');
                $table->string( 'activation_token' )->nullable();
                $table->datetime( 'activation_expiration' )->nullable();
                $table->integer( 'total_sales_count' )->default(0);
                $table->float( 'total_sales' )->default(0);
                $table->rememberToken();
                $table->timestamps();
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
        Schema::dropIfExists('nexopos_users');
    }
}
