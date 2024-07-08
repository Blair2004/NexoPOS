<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.convert.products-units' ] );
        $permission->name = __( 'Convert Products Units' );
        $permission->namespace = 'nexopos.convert.products-units';
        $permission->description = __( 'Let the user convert products' );
        $permission->save();

        Role::namespace( Role::ADMIN )->addPermissions( $permission );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where( 'namespace', 'nexopos.convert.products-units' )->first();

        if ( $permission instanceof Permission ) {
            $permission->removeFromRoles();
            $permission->delete();
        }
    }
};
