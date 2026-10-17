<?php

namespace Tests\Feature;

use App\Jobs\TriggerRecurringTransactionJob;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecurringTransactionMonthScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * TransactionService::getScheduledTransactions() places "5 days after the month starts" on the 6th.
     */
    public function test_transactions_relative_to_the_month_are_triggered_on_their_day(): void
    {
        [ $account, $author ] = $this->fixtures();

        $afterStart = $this->recurringTransaction( Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS, 5, $account, $author );
        $beforeEnd = $this->recurringTransaction( Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS, 5, $account, $author );

        $events = $this->scheduledTransactionEvents();
        $ids = [ $afterStart->id, $beforeEnd->id ];

        $this->assertSame( [ $afterStart->id ], $this->triggeredOn( $events, '2026-11-06 00:00:00', $ids ) );
        $this->assertSame( [ $beforeEnd->id ], $this->triggeredOn( $events, '2026-10-26 00:00:00', $ids ) );
        $this->assertSame( [], $this->triggeredOn( $events, '2026-10-15 00:00:00', $ids ) );
    }

    /**
     * An offset of 30 days is due on March 31 only: March 3 minus 30 days is February 1, which
     * must not trigger it a second time.
     */
    public function test_a_long_offset_is_not_triggered_twice_across_february(): void
    {
        [ $account, $author ] = $this->fixtures();
        $transaction = $this->recurringTransaction( Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS, 30, $account, $author );
        $events = $this->scheduledTransactionEvents();
        $ids = [ $transaction->id ];

        $this->assertSame( [], $this->triggeredOn( $events, '2026-03-03 00:00:00', $ids ) );
        $this->assertSame( [ $transaction->id ], $this->triggeredOn( $events, '2026-03-31 00:00:00', $ids ) );
    }

    /**
     * @return array{0: TransactionAccount, 1: User}
     */
    private function fixtures(): array
    {
        $author = new User;
        $author->username = 'recurring-' . Str::lower( Str::random( 8 ) );
        $author->email = $author->username . '@nexopos.test';
        $author->password = bcrypt( Str::random( 16 ) );
        $author->active = true;
        $author->save();

        $account = new TransactionAccount;
        $account->name = 'Recurring expenses';
        $account->author_id = $author->id;
        $account->save();

        return [ $account, $author ];
    }

    private function recurringTransaction( string $occurrence, int $value, TransactionAccount $account, User $author ): Transaction
    {
        $transaction = new Transaction;
        $transaction->name = 'Recurring ' . $occurrence;
        $transaction->account_id = $account->id;
        $transaction->value = 100;
        $transaction->recurring = true;
        $transaction->active = true;
        $transaction->type = Transaction::TYPE_RECURRING;
        $transaction->occurrence = $occurrence;
        $transaction->occurrence_value = $value;
        $transaction->author_id = $author->id;
        $transaction->saveQuietly();

        return $transaction;
    }

    /**
     * The events that routes/console/transactions.php registers for the transactions above.
     */
    private function scheduledTransactionEvents(): array
    {
        $schedule = app( Schedule::class );
        $existing = count( $schedule->events() );

        include base_path( 'routes/console/transactions.php' );

        return array_slice( $schedule->events(), $existing );
    }

    /**
     * Ids of the given transactions whose job the scheduler dispatches at that time (only the
     * events due then run, as with schedule:run).
     */
    private function triggeredOn( array $events, string $now, array $ids ): array
    {
        Bus::fake();
        Carbon::setTestNow( $now );

        foreach ( $events as $event ) {
            if ( $event->isDue( app() ) && $event->filtersPass( app() ) ) {
                $event->run( app() );
            }
        }

        return Bus::dispatched( TriggerRecurringTransactionJob::class )
            ->map( fn( TriggerRecurringTransactionJob $job ) => $job->transaction->id )
            ->intersect( $ids )
            ->sort()
            ->values()
            ->all();
    }
}
