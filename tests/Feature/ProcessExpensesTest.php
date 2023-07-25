<?php

namespace Tests\Feature;

use App\Fields\ReccurringTransactionFields;
use App\Fields\ScheduledTransactionFields;
use App\Jobs\DetectScheduledTransactionsJob;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Models\Transaction;
use App\Services\DateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class ProcessExpensesTest extends TestCase
{
    use WithAuthentication, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_start_of_month()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Rent',
            'occurrence' => Transaction::OCCURRENCE_START_OF_MONTH,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_scheduled_expense()
    {
        $this->attemptAuthenticate();

        $date = app()->make( DateService::class );

        $expense = $this->createExpense([
            'name' => 'Scheduled Expense',
            'occurrence' => null,
            'active' => true,
            'recurring' => false,
            'type' => ScheduledTransactionFields::getIdentifier(),
            'scheduled_date' => $date->copy()->addMinutes(2)->toDateTimeString(),
        ]);

        $this->executeScheduledExpense( $expense );
    }

    public function executeScheduledExpense( $expense )
    {
        $scheduledCarbon = Carbon::parse( $expense->scheduled_date );

        ns()->date->setDateTimeFrom( $scheduledCarbon );

        DetectScheduledTransactionsJob::dispatchSync();

        $cashFlow = TransactionHistory::where( 'transaction_id', $expense->id )->first();

        $this->assertTrue( $cashFlow instanceof TransactionHistory, 'No cash flow record were saved after the scheduled expense.' );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_specific_day()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Rent',
            'occurrence' => Transaction::OCCURRENCE_SPECIFIC_DAY,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_end_of_month()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Delivery',
            'occurrence' => Transaction::OCCURRENCE_END_OF_MONTH,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_middle_of_month()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Reccurring Middle Of Month',
            'occurrence' => Transaction::OCCURRENCE_MIDDLE_OF_MONTH,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_x_after_month_starts()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Reccurring On Month Starts',
            'occurrence' => Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reccurring_expense_on_x_before_month_ends()
    {
        $this->attemptAuthenticate();

        $expense = $this->createExpense([
            'name' => 'Reccurring On Month Ends',
            'occurrence' => Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    private function createExpense( $config )
    {
        /**
         * We don't want to execute multiple expenses
         * during this test.
         */
        Transaction::truncate();

        $occurrence = $config[ 'occurrence' ] ?? Transaction::OCCURRENCE_START_OF_MONTH;

        $range = match ( $occurrence ) {
            Transaction::OCCURRENCE_START_OF_MONTH,
            Transaction::OCCURRENCE_END_OF_MONTH,
            Transaction::OCCURRENCE_MIDDLE_OF_MONTH => 1,
            Transaction::OCCURRENCE_SPECIFIC_DAY => 28,
            Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS,
            Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => 10,
            default => 1,
        };

        $response = $this->json( 'POST', '/api/transactions', [
            'name' => $config[ 'name' ] ?? $this->faker->paragraph(2),
            'active' => true,
            'category_id' => TransactionAccount::get( 'id' )->random()->id,
            'description' => $this->faker->paragraph(5),
            'value' => $this->faker->randomNumber(2),
            'recurring' => $config[ 'recurring' ] ?? true,
            'type' => $config[ 'type' ] ?? ReccurringTransactionFields::getIdentifier(),
            'group_id' => $config[ 'group_id' ] ?? null,
            'occurrence' => $occurrence,
            'scheduled_date' => $config[ 'scheduled_date'] ?? null,
            'occurrence_value' => $this->faker->numberBetween(1, $range ),
        ]);

        $response->assertJsonPath( 'status', 'success' );
        $responseJson = $response->json();
        $transaction = $responseJson[ 'data' ][ 'transaction' ];

        return Transaction::find( $transaction[ 'id' ] );
    }

    private function executeRecurringExpense( $transaction )
    {
        $currentDay = now()->startOfMonth();

        /**
         * @var TransctionService
         */
        $transactionService = app()->make( TransctionService::class );

        while ( ! $currentDay->isLastOfMonth() ) {
            $result = $transactionService->handleRecurringExpenses( $currentDay );

            switch( $transaction->occurrence ) {
                case Transaction::OCCURRENCE_START_OF_MONTH:
                    if ( (int) $currentDay->day === 1 ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the first day of the month.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Transaction::OCCURRENCE_END_OF_MONTH:
                    if ( $currentDay->isLastOfMonth() ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the last day of the month.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Transaction::OCCURRENCE_MIDDLE_OF_MONTH:
                    if ( (int) $currentDay->day === 15 ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the middle of the month.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Transaction::OCCURRENCE_SPECIFIC_DAY:
                    if ( (int) $currentDay->day === (int) $transaction->occurrence_value ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at a specific day of the month.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS:
                    if ( (int) $currentDay->copy()->startOfMonth()->addDays( $transaction->occurrence_value )->isSameDay( $currentDay ) ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered x days after the month started.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS:
                    if ( (int) $currentDay->copy()->endOfMonth()->subDays( $transaction->occurrence_value )->isSameDay( $currentDay ) ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered x days before the month ended.'
                        );

                        $resultTransactionId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->transaction_id ?? 0;

                        $this->assertTrue(
                            $resultTransactionId === (int) $transaction->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
            }

            $currentDay->addDay();
        }
    }
}
