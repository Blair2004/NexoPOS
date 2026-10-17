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

    public function test_transactions_relative_to_the_month_are_triggered_on_their_day(): void
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

        $afterStart = $this->recurringTransaction( Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS, 5, $account, $author );
        $beforeEnd = $this->recurringTransaction( Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS, 5, $account, $author );

        $events = $this->scheduledTransactionEvents();
        $ids = [ $afterStart->id, $beforeEnd->id ];

        // TransactionService::getScheduledTransactions() places "5 days after the month starts" on the 6th.
        $this->assertSame( [ $afterStart->id ], $this->triggeredOn( $events, '2026-11-06 00:01:00', $ids ) );
        $this->assertSame( [ $beforeEnd->id ], $this->triggeredOn( $events, '2026-10-26 00:01:00', $ids ) );
        $this->assertSame( [], $this->triggeredOn( $events, '2026-10-15 00:01:00', $ids ) );
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
     * Ids of the given transactions whose job was dispatched at that time.
     */
    private function triggeredOn( array $events, string $now, array $ids ): array
    {
        Bus::fake();
        Carbon::setTestNow( $now );

        foreach ( $events as $event ) {
            $event->run( app() );
        }

        return Bus::dispatched( TriggerRecurringTransactionJob::class )
            ->map( fn( TriggerRecurringTransactionJob $job ) => $job->transaction->id )
            ->intersect( $ids )
            ->sort()
            ->values()
            ->all();
    }
}
