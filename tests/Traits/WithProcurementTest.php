<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\CashFlow;
use App\Models\Procurement;
use App\Models\Provider;
use App\Services\TestService;

trait WithProcurementTest
{
    protected function attemptCreateAnUnpaidProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $provider = Provider::get()->random();
        $currentExpenseValue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general' => [
                'payment_status' => 'unpaid',
                'provider_id' => $provider->id,
                'delivery_status' => Procurement::DELIVERED,
            ],
        ]);

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/procurements', $procurementsDetails );

        $response->assertOk();
        $decode = json_decode( $response->getContent(), true );

        $newExpensevalue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurement = $decode[ 'data' ][ 'procurement' ];

        /**
         * Step 1: there shouldn't be a change on the expenses
         */
        $this->assertTrue( (float) $currentExpenseValue === (float) $newExpensevalue, 'The expenses has changed for an unpaid procurement.' );
        $this->assertTrue( (float) $provider->amount_due !== (float) $provider->fresh()->amount_due, 'The due amount for the provider hasn\'t changed, while it should.' );

        /**
         * Step 2: update the procurement to paid
         */
        $currentExpenseValue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/nexopos/v4/procurements/' . $procurement[ 'id' ] . '/set-as-paid' );

        $response->assertOk();
        $decode = json_decode( $response->getContent(), true );

        $newExpensevalue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $existingExpense = CashFlow::where( 'procurement_id', $procurement[ 'id' ] )->first();

        $this->assertEquals( ns()->currency->getRaw( $existingExpense->value ), ns()->currency->getRaw( $procurement[ 'cost' ] ), 'The cash flow value doesn\'t match the procurement cost.' );
        $this->assertTrue( $existingExpense instanceof CashFlow, 'No cash flow was created after the procurement was marked as paid.' );
        $this->assertTrue( (float) $currentExpenseValue !== (float) $newExpensevalue, 'The expenses hasn\'t changed for the previously unpaid procurement.' );
    }

    protected function attemptCreateProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $currentExpenseValue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/procurements', $procurementsDetails );

        $response->assertOk();

        /**
         * We'll proceed to the verification
         * and check if the accounts are valid.
         */
        $responseData = json_decode( $response->getContent(), true );
        $newExpensevalue = CashFlow::where( 'expense_category_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $existingExpense = CashFlow::where( 'procurement_id', $responseData[ 'data' ][ 'procurement' ][ 'id' ] )->first();

        $this->assertTrue( $existingExpense instanceof CashFlow, __( 'No cash flow were created for the created procurement.' ) );

        $response->assertJson([ 'status' => 'success' ]);

        /**
         * We'll check if the expense value
         * has increased due to the procurement
         */
        $this->assertEquals(
            Currency::raw( $newExpensevalue ),
            Currency::raw( (float) $currentExpenseValue + (float) $responseData[ 'data' ][ 'procurement' ][ 'cost' ] )
        );
    }
}
