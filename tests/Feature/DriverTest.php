<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\UserAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class DriverTest extends TestCase
{
    use WithFaker, WithAuthentication;

    /**
     * A basic feature test example.
     */
    public function test_create_driver_from_users_crud(): void
    {
        $this->attemptAuthenticate();

        $password = $this->faker->password( 8, 20 );
        $role = Role::where( 'namespace', Role::DRIVER )->first();

        $configuration = [
            'username' => $this->faker->username(),
            'general' => [
                'email' => $this->faker->email(),
                    'password' => $password,
                'password_confirm' => $password,
                'roles' => [ $role->id ],
                'active' => 1, // true
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', '/api/crud/ns.users', $configuration );

        $response   =   $response->json();

        $driver   =   Driver::findOrFail( $response[ 'data' ][ 'entry' ][ 'id' ] );

        $this->assertTrue( $driver->status == Driver::STATUS_OFFLINE, 'The default status of a driver should be offline.' );
        $this->assertTrue( UserAttribute::where( 'user_id', $driver->id )->exists(), 'The driver attribute should be created.' );
    }

    public function test_create_driver_from_crud(): void
    {
        $this->attemptAuthenticate();

        $password = $this->faker->password( 8, 20 );
        $role = Role::where( 'namespace', Role::DRIVER )->first();

        $configuration = [
            'username' => $this->faker->username(),
            'general' => [
                'email' => $this->faker->email(),
                'password' => $password,
                'password_confirm' => $password,
                'active' => 1, // true
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', '/api/crud/ns.drivers', $configuration );

        $response   =   $response->json();

        $driver   =   Driver::findOrFail( $response[ 'data' ][ 'entry' ][ 'id' ] );

        $this->assertTrue( $driver instanceof Driver, 'The driver should be an instance of the Driver model.' );
        $this->assertTrue( $driver->status == Driver::STATUS_OFFLINE, 'The default status of a driver should be offline.' );
        $this->assertTrue( UserAttribute::where( 'user_id', $driver->id )->exists(), 'The driver attribute should be created.' );
    }
}
