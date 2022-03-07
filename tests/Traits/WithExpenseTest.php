<?php
namespace Tests\Traits;

use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

trait WithExpenseTest
{
    protected function attemptCreateExpensesCategories()
    {
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

    protected function attemptCreateExpenses()
    {
        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category          =   AccountType::find(1);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.expenses', [
                'name'          =>  __( 'Store Rent' ),
                'general'       =>  [
                    'active'        =>  true,
                    'value'         =>  1500,
                    'recurring'     =>  false,
                    'category_id'   =>  $category->id,
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category          =   AccountType::find(1);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.expenses', [
                'name'          =>  __( 'Material Delivery' ),
                'general'       =>  [
                    'active'        =>  true,
                    'value'         =>  300,
                    'recurring'     =>  false,
                    'category_id'   =>  $category->id,
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $category      =   AccountType::find(2);

        $role       =   Role::get()->shuffle()->first();
        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.expenses', [
                'name'          =>  __( 'Store Rent' ),
                'general'       =>  [
                    'active'        =>  true,
                    'value'         =>  1500,
                    'recurring'     =>  false,
                    'category_id'   =>  $category->id,
                    'occurence'     =>  'month_starts',
                    'group_id'      =>  $role->id,
                ]
            ]);

        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response->assertStatus(200);
    }
}