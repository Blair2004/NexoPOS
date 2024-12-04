<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class TestOtherGetRoutes extends TestCase
{
    use WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_all_api_routes()
    {
        $this->attemptAuthenticate();

        $routes = Route::getRoutes();

        foreach ( $routes as $route ) {
            $uri = $route->uri();

            if ( in_array( 'GET', $route->methods() ) ) {
                /**
                 * We'll test both known API and dashboard to see if
                 * there is any error thrown.
                 */
                if ( preg_match( '/^api\//', $uri ) && ! preg_match( '/\{\w+\??\}/', $uri ) ) {
                    $response = $this->withSession( $this->app[ 'session' ]->all() )
                        ->json( 'GET', $uri );

                    /**
                     * Route that allow exception
                     */
                    if ( in_array( $response->status(), [ 200, 403 ] ) ) {
                        if ( in_array( $uri, [
                            'api/cash-registers/used',
                        ] ) ) {
                            $response->assertStatus( 403 );
                        } else {
                            $response->assertStatus( 200 );
                        }
                    } else {
                        throw new Exception( 'Not supported status detected.' );
                    }
                }
            }
        }
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_dashboard_get_routes()
    {
        $routes = Route::getRoutes();
        $user = $this->attemptGetAnyUserFromRole();

        foreach ( $routes as $route ) {
            $uri = $route->uri();

            if ( in_array( 'GET', $route->methods() ) ) {
                /**
                 * We'll test both known API and dashboard to see if
                 * there is any error thrown.
                 */
                if ( ( strstr( $uri, 'dashboard' ) ) && ! strstr( $uri, 'api/' ) && ! preg_match( '/\{\w+\??\}/', $uri ) ) {
                    $response = $this->actingAs( $user )
                        ->json( 'GET', $uri );

                    if ( $response->status() === 302 ) {
                        $this->assertTrue( in_array( $uri, [
                            'dashboard/accounting/transactions/create',
                        ] ) );
                    } else {
                        if ( $response->status() == 200 ) {
                            if ( $uri === 'dashboard/pos' ) {
                                $response->assertSee( 'ns-pos' ); // pos component
                            } else {
                                $response->assertSee( 'dashboard-body' );
                            }
                        } else {
                            throw new Exception( 'Not supported status detected.' );
                        }
                    }
                }
            }
        }
    }
}
