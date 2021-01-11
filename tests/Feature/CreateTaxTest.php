<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaxGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateTaxTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $group          =   TaxGroup::get()->shuffle()->first();

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.taxes', [
                'name'          =>  __( 'SGST' ),
                'general'       =>  [
                    'rate'      =>  5.5,
                    'tax_group_id'  =>  $group->id
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.taxes', [
                'name'          =>  __( 'CGST' ),
                'general'       =>  [
                    'rate'      =>  6.5,
                    'tax_group_id'  =>  $group->id
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
