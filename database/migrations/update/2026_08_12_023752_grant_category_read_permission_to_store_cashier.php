<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSION = 'nexopos.read.categories';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cashier = Role::namespace( Role::STORECASHIER );
        $permission = Permission::namespace( self::PERMISSION );

        if ( $cashier instanceof Role && $permission instanceof Permission ) {
            $cashier->addPermissions( $permission );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cashier = Role::namespace( Role::STORECASHIER );
        $permission = Permission::namespace( self::PERMISSION );

        if ( $cashier instanceof Role && $permission instanceof Permission ) {
            $cashier->removePermissions( self::PERMISSION );
        }
    }
};
