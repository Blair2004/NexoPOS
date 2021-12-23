<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;

class RenderCrudFormTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        Auth::loginUsingId( Role::namespace( 'admin' )->users->first()->id );

        /**
         * we'll test all crud forms
         * and see if it throws any php errors
         */
        $cruds  =   [
            'cash-registers',
            'customers',
            'customers/groups',
            'customers/rewards-system',
            'customers/coupons',
            'procurements',
            'providers',
            'expenses',
            'accounting/accounts',
            'products',
            'products/categories',
            'taxes',
            'taxes/groups',
            'units',
            'units/groups',
            'users/roles',
            'users',
            'orders/payments-types'
        ];

        foreach( $cruds as $crud ) {
            dump( 'Testing : ' . $crud );
            
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/dashboard/' . $crud . '/create' );
    
            $response->assertDontSee( 'exception' );
        }
    }
}
