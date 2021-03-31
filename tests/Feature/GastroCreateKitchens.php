<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GastroCreateKitchens extends TestCase
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

        $kitchenNames   =   [ __( 'First Kitchen' ), __( 'Second Kitchen' ), __( 'Third Kitchen' ) ];
        $categories     =   ProductCategory::get()
            ->map( fn( $category ) => $category->id )
            ->toArray();

        foreach( $kitchenNames as $index => $name ) {
            $response       =   $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/crud/ns.gastro-kitchens', [
                    'name'              =>  $name,
                    'general'           =>  [
                        'status'        =>  'enable',
                        'categories'    =>  array_chunk( $categories, count( $categories ) / 3 )[ $index ],
                    ]
                ]);
    
            $response->assertJsonPath( 'status', 'success' );
        }
    }
}
