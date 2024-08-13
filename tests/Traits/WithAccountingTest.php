<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\ActiveTransactionHistory;
use App\Models\DashboardDay;
use App\Models\Procurement;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Services\ReportService;
use App\Services\TestService;
use Illuminate\Support\Facades\Auth;

trait WithAccountingTest
{
    protected function attemptCreateBankingAccounts()
    {
        $accounts = [
            [
                'name' => __( 'Stock Procurement' ),
                'account' => TransactionHistory::ACCOUNT_PROCUREMENTS,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Sales' ),
                'account' => TransactionHistory::ACCOUNT_SALES,
                'operation' => TransactionHistory::OPERATION_CREDIT,
            ], [
                'name' => __( 'Customer Credit (cash-in)' ),
                'account' => TransactionHistory::ACCOUNT_CUSTOMER_CREDIT,
                'operation' => TransactionHistory::OPERATION_CREDIT,
            ], [
                'name' => __( 'Customer Credit (cash-out)' ),
                'account' => TransactionHistory::ACCOUNT_CUSTOMER_DEBIT,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Sale Refunds' ),
                'account' => TransactionHistory::ACCOUNT_REFUNDS,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Stock Return (spoiled items)' ),
                'account' => TransactionHistory::ACCOUNT_SPOILED,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Stock Return (unspoiled items)' ),
                'account' => TransactionHistory::ACCOUNT_UNSPOILED,
                'operation' => TransactionHistory::OPERATION_CREDIT,
            ], [
                'name' => __( 'Cash Register (cash-in)' ),
                'account' => TransactionHistory::ACCOUNT_REGISTER_CASHING,
                'operation' => TransactionHistory::OPERATION_CREDIT,
            ], [
                'name' => __( 'Cash Register (cash-out)' ),
                'account' => TransactionHistory::ACCOUNT_REGISTER_CASHOUT,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Liabilities' ),
                'account' => TransactionHistory::ACCOUNT_LIABILITIES,
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ], [
                'name' => __( 'Equity' ),
                'account' => TransactionHistory::ACCOUNT_EQUITY,
                'operation' => TransactionHistory::OPERATION_CREDIT,
            ],
        ];

        foreach ( $accounts as $account ) {
            $transactionAccount = TransactionAccount::where( 'account', $account[ 'account' ] )
                ->first();

            /**
             * in case the test is executed twice, we don't want to repeatedly
             * record the same account on the database.
             */
            if ( ! $transactionAccount instanceof TransactionAccount ) {
                $response = $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/crud/ns.transactions-accounts', [
                        'name' => $account[ 'name' ],
                        'general' => [
                            'operation' => $account[ 'operation' ],
                            'author' => Auth::id(),
                            'counter_account_id' => 0,
                            'account' => $account[ 'account' ],
                        ],
                    ] );

                $response->assertStatus( 200 );
            }
        }

        ns()->option->set( 'ns_procurement_cashflow_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_PROCUREMENTS )->first()->id );
        ns()->option->set( 'ns_sales_cashflow_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_SALES )->first()->id );
        ns()->option->set( 'ns_customer_crediting_cashflow_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_CUSTOMER_CREDIT )->first()->id );
        ns()->option->set( 'ns_customer_debitting_cashflow_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_CUSTOMER_DEBIT )->first()->id );
        ns()->option->set( 'ns_sales_refunds_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_REFUNDS )->first()->id );
        ns()->option->set( 'ns_stock_return_spoiled_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_SPOILED )->first()->id );
        ns()->option->set( 'ns_stock_return_unspoiled_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_UNSPOILED )->first()->id );
        ns()->option->set( 'ns_stock_return_unspoiled_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_UNSPOILED )->first()->id );
        ns()->option->set( 'ns_liabilities_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_LIABILITIES )->first()->id );
        ns()->option->set( 'ns_equity_account', TransactionAccount::where( 'account', TransactionHistory::ACCOUNT_EQUITY )->first()->id );
    }

    protected function attemptCheckProcurementRecord( $procurement_id )
    {
        /**
         * @var Procurement
         */
        $procurement = Procurement::find( $procurement_id );

        /**
         * @var TransactionHistory
         */
        $transactionHistory = TransactionHistory::where( 'procurement', $procurement_id )->first();

        $assignedCategoryID = ns()->option->get( 'ns_procurement_cashflow_account' );

        $this->assertTrue( $procurement instanceof Procurement, __( 'Unable to retreive the procurement using the id provided.' ) );
        $this->assertTrue( $transactionHistory instanceof TransactionHistory, __( 'Unable to retreive the cashflow using the provided procurement id' ) );
        $this->assertTrue( $transactionHistory->transaction_account_id == $assignedCategoryID, __( 'The assigned account doens\'t match what was set for procurement cash flow.' ) );
        $this->assertEquals( $procurement->cost, $transactionHistory->value, __( 'The cash flow records doesn\'t match the procurement cost.' ) );
    }

    protected function attemptCheckSalesTaxes()
    {
        /**
         * @var ReportService $reportService
         */
        $reportService = app()->make( ReportService::class );

        /**
         * This will be used as a reference to check if
         * there has been any change on the report.
         */
        $reportService->computeDayReport(
            ns()->date->copy()->startOfDay()->toDateTimeString(),
            ns()->date->copy()->endOfDay()->toDateTimeString(),
        );

        $dashboardDay = DashboardDay::forToday();

        /**
         * Step 1 : let's check if performing a
         * procurement will affect the expenses.
         *
         * @var TestService
         */
        $procurementsDetails = app()->make( TestService::class );
        $procurementData = $procurementsDetails->prepareProcurement( ns()->date->now(), [
            'total_products' => 2,
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementData );

        $response->assertStatus( 200 );

        $array = json_decode( $response->getContent(), true );
        $procurement = $array[ 'data' ][ 'procurement' ];

        $this->assertTrue( ActiveTransactionHistory::where( 'procurement_id', $procurement[ 'id' ] )->exists(), __( 'The procurement hasn\'t affected the cash flow.' ) );

        $currentDashboardDay = DashboardDay::forToday();

        $expenseCategoryID = ns()->option->get( 'ns_procurement_cashflow_account' );
        $totalExpenses = TransactionHistory::where( 'created_at', '>=', $dashboardDay->range_starts )
            ->where( 'created_at', '<=', $dashboardDay->range_ends )
            ->where( 'transaction_account_id', $expenseCategoryID )
            ->where( 'procurement_id', $procurement[ 'id' ] )
            ->where( 'operation', TransactionHistory::OPERATION_DEBIT )
            ->sum( 'value' );

        $this->assertEquals(
            Currency::define( $dashboardDay->day_expenses )->additionateBy( $procurement[ 'cost' ] )->toFloat(),
            Currency::raw( $currentDashboardDay->day_expenses ),
            __( 'hasn\'t affected the expenses' )
        );

        $this->assertEquals(
            Currency::raw( $totalExpenses ),
            Currency::raw( $procurement[ 'cost' ] ),
            __( 'The procurement doesn\'t match with the cash flow.' )
        );
    }
}
