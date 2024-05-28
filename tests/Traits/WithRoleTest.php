<?php

namespace Tests\Traits;

use App\Crud\RolesCrud;
use App\Models\Role;

trait WithRoleTest
{
    use WithCrud;

    public function attemptCreateReservedRole()
    {
        $role = Role::whereIn( 'namespace', [
            Role::ADMIN,
            Role::STOREADMIN,
            Role::STORECASHIER,
            Role::USER,
        ] )->first();

        $response = $this->submitRequest( ( new RolesCrud )->getNamespace(), [
            'name' => $role->name,
            'general' => [
                'namespace' => $role->namespace,
            ],
        ] );

        /**
         * The attempt should fail.
         */
        $response->assertStatus( 422 );
    }

    public function attemptEditReservedRole()
    {
        $role = Role::whereIn( 'namespace', [
            Role::ADMIN,
            Role::STOREADMIN,
            Role::STORECASHIER,
            Role::USER,
        ] )->first();

        $this->submitRequest( ( new RolesCrud )->getNamespace() . '/' . $role->id, [
            'name' => $role->name,
            'general' => [
                'namespace' => $role->namespace,
                'dashid' => $role->dashid,
            ],
        ], 'PUT' );

        $newRole = $role->fresh();

        $this->assertTrue( $role->namespace === $newRole->namespace, 'The namespace has been updated.' );
    }

    public function attemptDeleteReservedRole()
    {
        $role = Role::whereIn( 'namespace', [
            Role::ADMIN,
            Role::STOREADMIN,
            Role::STORECASHIER,
            Role::USER,
        ] )->first();

        $response = $this->submitRequest( ( new RolesCrud )->getNamespace() . '/' . $role->id, [], 'DELETE' );

        /**
         * A system role can't be deleted
         */
        $response->assertStatus( 500 );
    }
}
