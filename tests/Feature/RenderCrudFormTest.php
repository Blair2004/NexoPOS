<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RenderCrudFormTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_render_crud_forms()
    {
        Auth::loginUsingId( Role::namespace( 'admin' )->users->first()->id );

        /**
         * we'll test all crud forms
         * and see if it throws any php errors
         */
        $cruds = [
            'cash-registers',
            'customers',
            'customers/groups',
            'customers/rewards-system',
            'customers/coupons',
            'procurements',
            'providers',
            'accounting/transactions',
            'accounting/accounts',
            'products',
            'products/categories',
            'taxes',
            'taxes/groups',
            'units',
            'units/groups',
            'users/roles',
            'users',
            'orders/payments-types',
        ];

        foreach ( $cruds as $crud ) {
            echo "* Testing :  $crud\n";

            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/dashboard/' . $crud . '/create' );

            $response->assertDontSee( 'exception' );
        }
    }
}
