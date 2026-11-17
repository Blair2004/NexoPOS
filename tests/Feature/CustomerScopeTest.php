<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerScopeTest extends TestCase
{
    public function test_administrators_with_the_customer_role_are_excluded_from_customers(): void
    {
        $administrator = $this->createUser( 'dual_role_admin' );
        $customer = $this->createUser( 'customer_only' );
        $administratorRole = Role::query()->firstOrCreate(
            ['namespace' => Role::ADMIN],
            [
                'name' => 'Administrator',
                'description' => 'Test administrator role.',
            ]
        );
        $customerRole = Role::query()->firstOrCreate(
            ['namespace' => Role::STORECUSTOMER],
            [
                'name' => 'Store Customer',
                'description' => 'Test customer role.',
            ]
        );

        try {
            DB::table( 'nexopos_users_roles_relations' )->insert( [
                [
                    'user_id' => $administrator->id,
                    'role_id' => $administratorRole->id,
                ],
                [
                    'user_id' => $administrator->id,
                    'role_id' => $customerRole->id,
                ],
                [
                    'user_id' => $customer->id,
                    'role_id' => $customerRole->id,
                ],
            ] );

            $this->assertNull( Customer::find( $administrator->id ) );
            $this->assertNotNull( Customer::find( $customer->id ) );
            $this->assertTrue( User::whereKey( $administrator->id )->exists() );
        } finally {
            DB::table( 'nexopos_users_roles_relations' )
                ->whereIn( 'user_id', [$administrator->id, $customer->id] )
                ->delete();
            User::whereKey( $administrator->id )->delete();
            User::whereKey( $customer->id )->delete();

            if ( $administratorRole->wasRecentlyCreated ) {
                $administratorRole->delete();
            }

            if ( $customerRole->wasRecentlyCreated ) {
                $customerRole->delete();
            }
        }
    }

    private function createUser( string $prefix ): User
    {
        $suffix = Str::lower( Str::random( 12 ) );

        return User::create( [
            'username' => $prefix . '_' . $suffix,
            'email' => $prefix . '_' . $suffix . '@example.test',
            'password' => bcrypt( Str::random( 24 ) ),
            'active' => true,
        ] );
    }
}
