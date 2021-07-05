<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nexopos_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string( 'name' )->unique();
            $table->string( 'namespace' )->unique();
            $table->string( 'dashid' )->nullable()->default( 'cashier' );
            $table->text( 'description' )->nullable();
            $table->integer( 'author' )->nullable(); // when provided match the user id
            $table->boolean( 'locked' )->default( true ); // means the role can be edited from the fronte
            $table->timestamps();
        });

        // Permissions Relation with Roles
        Schema::create('nexopos_role_permission', function (Blueprint $table) {
            $table->integer( 'permission_id' );
            $table->integer( 'role_id' );
            $table->primary([ 'permission_id', 'role_id' ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nexopos_roles');
        Schema::dropIfExists('nexopos_role_permission');
    }
}
