<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateExpenseCategoryTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateExpenses()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses-categories', [
                'name'          =>  __( 'Exploitation Expenses' ),
                'author'        =>  Auth::id(),
                'account'       =>  '000010',
                'operation'     =>  CashFlow::OPERATION_DEBIT
            ]);

        $response->assertStatus(200);
            
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses-categories', [
                'name'          =>  __( 'Employee Salaries' ),
                'author'        =>  Auth::id(),
                'account'       =>  '000011',
                'operation'     =>  CashFlow::OPERATION_DEBIT
            ]);

        $response->assertStatus(200);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/expenses-categories', [
                'name'          =>  __( 'Random Expenses' ),
                'author'        =>  Auth::id(),
                'account'       =>  '000012',
                'operation'     =>  CashFlow::OPERATION_DEBIT
            ]);

        $response->assertStatus(200);
    }
}
