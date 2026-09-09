<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSIONS = [
        'ns.oxen.use' => 'Oxen: Use assistant', 'ns.oxen.manage' => 'Oxen: Manage configuration',
        'ns.oxen.manage-tokens' => 'Oxen: Manage access tokens', 'ns.oxen.view-audit' => 'Oxen: View audit events',
        'ns.oxen.use-writes' => 'Oxen: Use write tools', 'ns.oxen.use-destructive' => 'Oxen: Use destructive tools',
    ];

    public function up(): void
    {
        foreach ( self::PERMISSIONS as $namespace => $name ) {
            $permission = Permission::query()->firstOrCreate( [ 'namespace' => $namespace ], [ 'name' => __m( $name, 'NsOxen' ), 'description' => __m( $name, 'NsOxen' ) ] );
            foreach ( [ Role::ADMIN, Role::STOREADMIN ] as $roleNamespace ) {
                Role::namespace( $roleNamespace )?->addPermissions( $permission );
            }
        }
    }

    public function down(): void
    {
        $ids = Permission::query()->whereIn( 'namespace', array_keys( self::PERMISSIONS ) )->pluck( 'id' );
        RolePermission::query()->whereIn( 'permission_id', $ids )->delete();
        Permission::query()->whereIn( 'id', $ids )->delete();
    }
};
