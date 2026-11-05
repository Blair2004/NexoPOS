<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        require __DIR__ . '/../../permissions/permissions-access.php';

        foreach ( [ Role::ADMIN, Role::STOREADMIN ] as $namespace ) {
            Role::namespace( $namespace )?->addPermissions( [
                'create.permissions-access',
                'read.permissions-access',
                'update.permissions-access',
                'delete.permissions-access',
                'delete-all.permissions-access',
            ] );
        }

        Role::namespace( Role::STORECASHIER )?->addPermissions( [
            'create.permissions-access',
            'delete.permissions-access',
        ] );

        Role::whereIn( 'namespace', [ Role::ADMIN, Role::STOREADMIN, Role::STORECASHIER ] )
            ->get()->each( function ( Role $role ): void {
                $role->users()->each( fn( $user ) => Cache::forget( 'ns-all-permissions-' . $user->id ) );
            } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep authorization data on rollback so older code cannot remove custom role assignments.
    }
};
