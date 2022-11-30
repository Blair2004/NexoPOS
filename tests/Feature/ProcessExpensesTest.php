<?php

namespace Tests\Feature;

use App\Fields\RecurringExpenseFields;
use App\Fields\ScheduledExpenseField;
use App\Jobs\DetectScheduledExpenseJob;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Expense;
use App\Services\DateService;
use App\Services\ExpenseService;
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
            'occurrence' => Expense::OCCURRENCE_START_OF_MONTH,
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
            'type' => ScheduledExpenseField::getIdentifier(),
            'scheduled_date' => $date->copy()->addMinutes(2)->toDateTimeString(),
        ]);

        $this->executeScheduledExpense( $expense );
    }

    public function executeScheduledExpense( $expense )
    {
        $scheduledCarbon = Carbon::parse( $expense->scheduled_date );

        ns()->date->setDateTimeFrom( $scheduledCarbon );

        DetectScheduledExpenseJob::dispatchSync();

        $cashFlow = CashFlow::where( 'expense_id', $expense->id )->first();

        $this->assertTrue( $cashFlow instanceof CashFlow, 'No cash flow record were saved after the scheduled expense.' );
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
            'occurrence' => Expense::OCCURRENCE_SPECIFIC_DAY,
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
            'occurrence' => Expense::OCCURRENCE_END_OF_MONTH,
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
            'occurrence' => Expense::OCCURRENCE_MIDDLE_OF_MONTH,
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
            'occurrence' => Expense::OCCURRENCE_X_AFTER_MONTH_STARTS,
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
            'occurrence' => Expense::OCCURRENCE_X_BEFORE_MONTH_ENDS,
        ]);

        $this->executeRecurringExpense( $expense );
    }

    private function createExpense( $config )
    {
        /**
         * We don't want to execute multiple expenses
         * during this test.
         */
        Expense::truncate();

        $occurrence = $config[ 'occurrence' ] ?? Expense::OCCURRENCE_START_OF_MONTH;

        $range = match ( $occurrence ) {
            Expense::OCCURRENCE_START_OF_MONTH,
            Expense::OCCURRENCE_END_OF_MONTH,
            Expense::OCCURRENCE_MIDDLE_OF_MONTH => 1,
            Expense::OCCURRENCE_SPECIFIC_DAY => 28,
            Expense::OCCURRENCE_X_BEFORE_MONTH_ENDS,
            Expense::OCCURRENCE_X_AFTER_MONTH_STARTS => 10,
            default => 1,
        };

        $response = $this->json( 'POST', '/api/expenses', [
            'name' => $config[ 'name' ] ?? $this->faker->paragraph(2),
            'active' => true,
            'category_id' => AccountType::get( 'id' )->random()->id,
            'description' => $this->faker->paragraph(5),
            'value' => $this->faker->randomNumber(2),
            'recurring' => $config[ 'recurring' ] ?? true,
            'type' => $config[ 'type' ] ?? RecurringExpenseFields::getIdentifier(),
            'group_id' => $config[ 'group_id' ] ?? null,
            'occurrence' => $occurrence,
            'scheduled_date' => $config[ 'scheduled_date'] ?? null,
            'occurrence_value' => $this->faker->numberBetween(1, $range ),
        ]);

        $response->assertJsonPath( 'status', 'success' );
        $responseJson = $response->json();
        $expense = $responseJson[ 'data' ][ 'expense' ];

        return Expense::find( $expense[ 'id' ] );
    }

    private function executeRecurringExpense( $expense )
    {
        $currentDay = now()->startOfMonth();

        /**
         * @var ExpenseService
         */
        $expenseService = app()->make( ExpenseService::class );

        while ( ! $currentDay->isLastOfMonth() ) {
            $result = $expenseService->handleRecurringExpenses( $currentDay );

            switch( $expense->occurrence ) {
                case Expense::OCCURRENCE_START_OF_MONTH:
                    if ( (int) $currentDay->day === 1 ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the first day of the month.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Expense::OCCURRENCE_END_OF_MONTH:
                    if ( $currentDay->isLastOfMonth() ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the last day of the month.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Expense::OCCURRENCE_MIDDLE_OF_MONTH:
                    if ( (int) $currentDay->day === 15 ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at the middle of the month.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Expense::OCCURRENCE_SPECIFIC_DAY:
                    if ( (int) $currentDay->day === (int) $expense->occurrence_value ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered at a specific day of the month.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Expense::OCCURRENCE_X_AFTER_MONTH_STARTS:
                    if ( (int) $currentDay->copy()->startOfMonth()->addDays( $expense->occurrence_value )->isSameDay( $currentDay ) ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered x days after the month started.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
                case Expense::OCCURRENCE_X_BEFORE_MONTH_ENDS:
                    if ( (int) $currentDay->copy()->endOfMonth()->subDays( $expense->occurrence_value )->isSameDay( $currentDay ) ) {
                        $this->assertTrue(
                            $result[ 'data' ][0][ 'status' ] === 'success',
                            'The expense hasn\'t been triggered x days before the month ended.'
                        );

                        $resultExpenseId = (int) $result[ 'data' ][0][ 'data' ][ 'histories' ]->first()->expense_id ?? 0;

                        $this->assertTrue(
                            $resultExpenseId === (int) $expense->id,
                            'The expense id is not matching the one that executed.'
                        );
                    }
                    break;
            }

            $currentDay->addDay();
        }
    }
}
