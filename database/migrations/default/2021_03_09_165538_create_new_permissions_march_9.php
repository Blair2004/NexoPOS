<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;

class CreateNewPermissionsMarch9 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission                 =   Permission::firstOrNew([ 'namespace' => 'nexopos.create.products-labels' ]);
        $permission->name           =   __( 'Create Products Labels' );
        $permission->description    =   __( 'Allow the user to create products labels' );
        $permission->save();

        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.store.administrator' )->addPermissions( $permission );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permission     =   Permission::namespace( 'nexopos.create.products-labels' );
        $permission->removeFromRoles();
        $permission->delete();        
    }
}
