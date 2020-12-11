<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCategoryTest extends TestCase
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

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name'                  =>  __( 'Computers' ),
                'general'               =>  [
                    'displays_on_pos'   =>  true
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $category       =   ProductCategory::first();

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name'          =>  __( 'Laptops' ),
                'general'       =>  [
                    'parent_id' =>  $category->id,
                    'displays_on_pos'    =>  true
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name'          =>  __( 'Desktop' ),
                'general'       =>  [
                    'parent_id' =>  $category->id,
                    'displays_on_pos'    =>  true
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
