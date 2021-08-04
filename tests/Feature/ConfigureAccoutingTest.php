<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use App\Models\CashFlow;
use App\Models\ExpenseCategory;
use Tests\TestCase;

class ConfigureAccoutingTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateBankingAccounts()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $accounts        =   [             
            [
                'name'  =>  __( 'Stock Procurement' ),
                'account'   =>  '000001',
                'operation' =>  CashFlow::OPERATION_DEBIT,
            ], [
                'name'  =>  __( 'Sales' ),
                'account'   =>  '000002',
                'operation' =>  CashFlow::OPERATION_CREDIT,
            ], [
                'name'  =>  __( 'Customer Credit (cash-in)' ),
                'account'   =>  '000003',
                'operation' =>  CashFlow::OPERATION_CREDIT,
            ], [
                'name'  =>  __( 'Customer Credit (cash-out)' ),
                'account'   =>  '000004',
                'operation' =>  CashFlow::OPERATION_DEBIT,
            ], [
                'name'  =>  __( 'Sale Refunds' ),
                'account'   =>  '000005',
                'operation' =>  CashFlow::OPERATION_DEBIT,
            ], [
                'name'  =>  __( 'Stock Return (spoiled items)' ),
                'account'   =>  '000006',
                'operation' =>  CashFlow::OPERATION_DEBIT,
            ], [
                'name'  =>  __( 'Stock Return (unspoiled items)' ),
                'account'   =>  '000007',
                'operation' =>  CashFlow::OPERATION_CREDIT,
            ], [
                'name'  =>  __( 'Cash Register (cash-in)' ),
                'account'   =>  '000008',
                'operation' =>  CashFlow::OPERATION_CREDIT,
            ], [
                'name'  =>  __( 'Cash Register (cash-out)' ),
                'account'   =>  '000009',
                'operation' =>  CashFlow::OPERATION_DEBIT,
            ],
        ];

        foreach( $accounts as $account ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/crud/ns.expenses-categories', [
                    'name'          =>  $account[ 'name' ],
                    'general'       =>  [
                        'operation'     =>  $account[ 'operation' ],
                        'author'        =>  Auth::id(),
                        'account'       =>  $account[ 'account' ],
                    ]
                ]);

            $response->assertStatus(200);
        }

        ns()->option->set( 'ns_procurement_cashflow_account', ExpenseCategory::where( 'account', '000001' )->first()->id );
        ns()->option->set( 'ns_sales_cashflow_account', ExpenseCategory::where( 'account', '000002' )->first()->id );
        ns()->option->set( 'ns_customer_crediting_cashflow_account', ExpenseCategory::where( 'account', '000003' )->first()->id );
        ns()->option->set( 'ns_customer_debitting_cashflow_account', ExpenseCategory::where( 'account', '000004' )->first()->id );
        ns()->option->set( 'ns_sales_refunds_account', ExpenseCategory::where( 'account', '000005' )->first()->id );
        ns()->option->set( 'ns_stock_return_spoiled_account', ExpenseCategory::where( 'account', '000006' )->first()->id );
        ns()->option->set( 'ns_stock_return_unspoiled_account', ExpenseCategory::where( 'account', '000007' )->first()->id );
        ns()->option->set( 'ns_cashregister_cashin_cashflow_account', ExpenseCategory::where( 'account', '000008' )->first()->id );
        ns()->option->set( 'ns_cashregister_cashout_cashflow_account', ExpenseCategory::where( 'account', '000009' )->first()->id );
    }
}
