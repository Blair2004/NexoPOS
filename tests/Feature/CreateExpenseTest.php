<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateExpenseTest extends TestCase
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

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category          =   ExpenseCategory::find(1);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses', [
                'name'          =>  __( 'Store Rent' ),
                'active'        =>  true,
                'value'         =>  1500,
                'recurring'     =>  false,
                'category_id'   =>  $category->id,
                'author'        =>  Auth::id()
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category          =   ExpenseCategory::find(1);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses', [
                'name'          =>  __( 'Material Delivery' ),
                'active'        =>  true,
                'value'         =>  300,
                'recurring'     =>  false,
                'category_id'   =>  $category->id,
                'author'        =>  Auth::id()
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category      =   ExpenseCategory::find(2);

        $role       =   Role::get()->shuffle()->first();
        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses', [
                'name'          =>  __( 'Store Rent' ),
                'active'        =>  true,
                'value'         =>  1500,
                'recurring'     =>  false,
                'category_id'   =>  $category->id,
                'occurence'     =>  'month_starts',
                'group_id'      =>  $role->id,
                'author'        =>  Auth::id()
            ]);

        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response->assertStatus(200);
    }
}
