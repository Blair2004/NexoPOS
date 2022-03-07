<?php
namespace Tests\Traits;

use App\Models\ProductCategory;

trait WithCategoryTest
{
    protected function attemptCreateCategory()
    {
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