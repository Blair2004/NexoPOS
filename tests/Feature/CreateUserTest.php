<?php

namespace Tests\Feature;

use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\PaymentType;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Provider;
use App\Models\RewardSystem;
use App\Models\Role;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\UsersService;
use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CreateUserTest extends TestCase
{
    use WithAuthentication, WithFaker;

    protected UsersService $users;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_users()
    {
        $this->attemptAuthenticate();

        $this->users = app()->make( UsersService::class );

        $role = Role::where( 'namespace', Role::ADMIN )->first();
        $password = Hash::make( Str::random( 20 ) );

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

        $response->assertJsonPath( 'status', 'success' );
        $response = $response->json();

        return compact( 'configuration', 'response' );
    }

    /**
     * @depends test_create_users
     */
    public function test_create_user_with_same_username_and_different_email( $data )
    {
        $this->attemptAuthenticate();

        extract( $data );
        /**
         * @var $configuration array
         * @var $response object
         */

        /**
         * @var UsersService
         */
        $this->users = app()->make( UsersService::class );

        /**
         * Step 1: create user with same username
         * but a different email
         */
        try {
            $this->users->setUser( [
                'username' => $configuration[ 'username' ],
                'email' => $this->faker->email(),
                'roles' => $configuration[ 'general' ][ 'roles' ],
                'active' => true,
            ] );
        } catch ( Exception $exception ) {
            $this->assertTrue( strstr( $exception->getMessage(), 'username' ) !== false );
        }

        return compact( 'configuration', 'response' );
    }

    /**
     * @depends test_create_user_with_same_username_and_different_email
     */
    public function test_create_user_with_same_email_and_different_username( $data )
    {
        $this->attemptAuthenticate();

        extract( $data );
        /**
         * @var $configuration array
         * @var $response object
         */

        /**
         * @var UsersService
         */
        $this->users = app()->make( UsersService::class );

        /**
         * Step 2: create user with same email
         * but a different username
         */
        try {
            $this->users->setUser( [
                'username' => $this->faker->userName(),
                'email' => $configuration[ 'general' ][ 'email' ],
                'roles' => $configuration[ 'general' ][ 'roles' ],
                'active' => true,
            ] );
        } catch ( Exception $exception ) {
            $this->assertTrue( strstr( $exception->getMessage(), 'email' ) !== false );
        }

        return compact( 'configuration', 'response' );
    }

    /**
     * @depends test_create_user_with_same_email_and_different_username
     */
    public function test_update_user_from_crud( $data )
    {
        $this->attemptAuthenticate();

        extract( $data );
        /**
         * @var $configuration array
         * @var $response object
         */
        $password = Hash::make( Str::random( 20 ) );
        $role = Role::where( 'namespace', Role::ADMIN )->first();

        /**
         * Step 3: Update user from Crud component
         */
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
            ->json( 'put', '/api/crud/ns.users/' . $response[ 'data' ][ 'entry' ][ 'id' ], $configuration );

        $response->assertJsonPath( 'status', 'success' );
        $result = json_decode( $response->getContent() );

        return $result->data->entry;
    }

    public function test_delete_user_having_orders()
    {
        $this->attemptAuthenticate();

        $user = Role::namespace( Role::ADMIN )->users()->first();

        $order = Order::where( 'author', '<>', $user->id )->first();

        if ( $order instanceof Order ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'delete', '/api/crud/ns.users/' . $order->author );

            $response->assertStatus( 403 );
        }
    }

    public function test_delete_users()
    {
        $this->test_create_users();

        $firstRole = Role::where( 'namespace', Role::ADMIN )->first();

        $firstRole->users()->where( 'nexopos_users.id', '<>', Auth::id() )->limit( 1 )->get()->each( function ( User $user ) {
            $this->attemptAuthenticate( $user );

            /**
             * Step 1: attempt to delete himself
             */
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'delete', '/api/crud/ns.users/' . $user->id );

            $response->assertStatus( 403 );
        } );
    }

    public function getValidRoutes()
    {
        return collect( Route::getRoutes() )->filter( function ( $route ) {
            $uri = $route->uri();

            /**
             * For now we'll only test dashboard routes
             */
            if ( strstr( $uri, 'dashboard' ) && ! strstr( $uri, 'api/' ) ) {
                return true;
            }

            return false;
        } );
    }

    /**
     * @depends test_create_users
     */
    public function test_explorable_routes_chunk_1()
    {
        $user = User::first();
        /**
         * Step 1: We'll attempt registering with the email
         * to cause a failure because of the email used
         */
        $validRoutes = $this->getValidRoutes();
        $chunk = count( $validRoutes ) / 5;
        $routes = collect( $validRoutes )->chunk( $chunk );

        $this->attemptAllRoutes( $user, $routes[0] );
    }

    /**
     * @depends test_explorable_routes_chunk_1
     */
    public function test_explorable_routes_chunk_2()
    {
        $user = User::first();
        /**
         * Step 1: We'll attempt registering with the email
         * to cause a failure because of the email used
         */
        $validRoutes = $this->getValidRoutes();
        $chunk = count( $validRoutes ) / 5;
        $routes = collect( $validRoutes )->chunk( $chunk );

        $this->attemptAllRoutes( $user, $routes[1] );
    }

    /**
     * @depends test_explorable_routes_chunk_2
     */
    public function test_explorable_routes_chunk_3()
    {
        $user = User::first();
        /**
         * Step 1: We'll attempt registering with the email
         * to cause a failure because of the email used
         */
        $validRoutes = $this->getValidRoutes();
        $chunk = count( $validRoutes ) / 5;
        $routes = collect( $validRoutes )->chunk( $chunk );

        $this->attemptAllRoutes( $user, $routes[2] );
    }

    /**
     * @depends test_explorable_routes_chunk_3
     */
    public function test_explorable_routes_chunk_4()
    {
        $user = User::first();
        /**
         * Step 1: We'll attempt registering with the email
         * to cause a failure because of the email used
         */
        $validRoutes = $this->getValidRoutes();
        $chunk = count( $validRoutes ) / 5;
        $routes = collect( $validRoutes )->chunk( $chunk );

        $this->attemptAllRoutes( $user, $routes[3] );
    }

    /**
     * @depends test_explorable_routes_chunk_4
     */
    public function test_explorable_routes_chunk_5()
    {
        $user = User::first();
        /**
         * Step 1: We'll attempt registering with the email
         * to cause a failure because of the email used
         */
        $validRoutes = $this->getValidRoutes();
        $chunk = count( $validRoutes ) / 5;
        $routes = collect( $validRoutes )->chunk( $chunk );

        $this->attemptAllRoutes( $user, $routes[4] );
    }

    public function attemptAllRoutes( $user, $routes )
    {
        $paramsModelBinding = [
            '/\{customer\}|\{customerAccountHistory\}/' => function () {
                $customerAccountHistory = CustomerAccountHistory::first()->id;
                $customer = $customerAccountHistory->customer->id;

                return compact( 'customerAccountHistory', 'customer' );
            },
            '/\{product\}/' => Product::class,
            '/\{provider\}/' => Provider::class,
            '/\{procurement\}/' => Procurement::class,
            '/\{category\}/' => ProductCategory::class,
            '/\{group\}/' => UnitGroup::class,
            '/\{unit\}/' => Unit::class,
            '/\{reward\}/' => RewardSystem::class,
            '/\{paymentType\}/' => PaymentType::class,
            '/\{user\}/' => User::class,
            '/\{order\}/' => Order::class,
            '/\{tax\}/' => Tax::class,
        ];

        foreach ( $routes as $route ) {
            $uri = $route->uri();

            /**
             * For now we'll only test dashboard routes
             */
            if ( strstr( $uri, 'dashboard' ) && ! strstr( $uri, 'api/' ) ) {
                /**
                 * For requests that doesn't support
                 * any paremeters
                 */
                foreach ( $paramsModelBinding as $expression => $binding ) {
                    if ( preg_match( $expression, $uri ) ) {
                        if ( is_array( $binding ) ) {
                            /**
                             * We want to replace all argument
                             * on the uri by the matching binding collection
                             */
                            foreach ( $binding as $parameter => $value ) {
                                $uri = preg_replace( '/\{' . $parameter . '\}/', $value, $uri );
                            }
                        } elseif ( is_string( $binding ) ) {
                            /**
                             * This are URI with a single parameter
                             * that are replaced once the binding is resolved.
                             */
                            $value = $binding::firstOrFail()->id;
                            $uri = preg_replace( $expression, $value, $uri );
                        }

                        break;
                    }
                }

                /**
                 * we believe all arguments are resolved
                 * if some argument remains, we won't test
                 * those routes.
                 */
                if ( preg_match( '/\{(.+)\}/', $uri ) === 0 ) {
                    $response = $this
                        ->actingAs( $user )
                        ->json( 'GET', $uri );

                    $status = $response->baseResponse->getStatusCode();

                    $this->assertTrue(
                        in_array( $status, [ 201, 200, 302, 403, 401 ] ),
                        'Unsupported HTTP response :' . $status . ' uri:' . $uri . ' user role:' . $user->roles->map( fn( $role ) => $role->namespace )->join( ',' )
                    );
                }
            }
        }
    }
}
