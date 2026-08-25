<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSION = 'nexopos.read.registers';

    private const USE_PERMISSION = 'nexopos.use.registers';

    private const READ_PRODUCT_PERMISSION = 'nexopos.read.products';

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

        $readPermission = Permission::namespace( self::USE_PERMISSION );
        if ( $cashier instanceof Role && $readPermission instanceof Permission ) {
            $cashier->addPermissions( $readPermission );
        }

        $readProductPermission = Permission::namespace( self::READ_PRODUCT_PERMISSION );
        if ( $cashier instanceof Role && $readProductPermission instanceof Permission ) {
            $cashier->addPermissions( $readProductPermission );
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

        $readPermission = Permission::namespace( self::USE_PERMISSION );
        if ( $cashier instanceof Role && $readPermission instanceof Permission ) {
            $cashier->removePermissions( self::USE_PERMISSION );
        }

        $readProductPermission = Permission::namespace( self::READ_PRODUCT_PERMISSION );
        if ( $cashier instanceof Role && $readProductPermission instanceof Permission ) {
            $cashier->removePermissions( self::READ_PRODUCT_PERMISSION );
        }
    }
};
