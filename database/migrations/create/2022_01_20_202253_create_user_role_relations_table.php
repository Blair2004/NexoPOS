<?php

use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleRelation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Determine whether the migration
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
        if ( ! Schema::hasTable( 'nexopos_users_roles_relations' ) ) {
            Schema::create( 'nexopos_users_roles_relations', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'role_id' );
                $table->integer( 'user_id' );
                $table->timestamps();
            } );
        }

        if ( Schema::hasColumn( 'nexopos_users', 'role_id' ) ) {
            Role::get()->each( function ( $role ) {
                User::where( 'role_id', $role->id )
                    ->get()
                    ->each( function ( $user ) use ( $role ) {
                        $relation = new UserRoleRelation;
                        $relation->user_id = $user->id;
                        $relation->role_id = $role->id;
                        $relation->save();
                    } );
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_users_roles_relations' );
    }
};
