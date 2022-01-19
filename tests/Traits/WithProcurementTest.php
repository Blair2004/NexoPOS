<?php
namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\CashFlow;
use App\Services\TestService;

trait WithProcurementTest
{
    protected function attemptCreateProcurement()
    {
        /**
         * @var TestService
         */
        $testService     =   app()->make( TestService::class );

        $currentExpenseValue    =   CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails    =   $testService->prepareProcurement( ns()->date->now(), [] );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/procurements', $procurementsDetails );

        $response->assertOk();

        /**
         * We'll proceed to the verification
         * and check if the accounts are valid.
         */
        $responseData       =   json_decode( $response->getContent(), true );
        $newExpensevalue    =   CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $existingExpense    =   CashFlow::where( 'procurement_id', $responseData[ 'data' ][ 'procurement' ][ 'id' ] )->first();

        $this->assertTrue( $existingExpense instanceof CashFlow, __( 'No cash flow were created for the created procurement.' ) );

        $response->assertJson([ 'status' => 'success' ]);

        /**
         * We'll check if the expense value
         * has increased due to the procurement
         */
        $this->assertEquals( 
            Currency::raw( $newExpensevalue ), 
            Currency::raw( ( float ) $currentExpenseValue + ( float ) $responseData[ 'data' ][ 'procurement' ][ 'cost' ] ) 
        );
    }
}