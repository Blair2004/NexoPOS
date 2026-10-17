<?php

namespace Tests\Feature;

use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceAccountSummaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_account_summary_loads_configured_categories_with_two_queries(): void
    {
        $rangeStart = '2026-10-01 00:00:00';
        $rangeEnd = '2026-10-01 23:59:59';

        foreach ( array_keys( config( 'accounting.accounts' ) ) as $category ) {
            $account = $this->createAccount( ucfirst( $category ), $category );
            $this->createHistory( $account, TransactionHistory::OPERATION_DEBIT, 10, '2026-10-01 10:00:00' );
            $this->createHistory( $account, TransactionHistory::OPERATION_CREDIT, 5, '2026-10-01 11:00:00' );
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $report = app( ReportService::class )->getAccountSummaryReport( $rangeStart, $rangeEnd );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount( 2, $queries );
        $this->assertCount( 5, $report['accounts'] );
        $this->assertSame( 50.0, (float) $report['debits'] );
        $this->assertSame( 25.0, (float) $report['credits'] );
    }

    private function createAccount( string $name, string $category ): TransactionAccount
    {
        $account = new TransactionAccount;
        $account->name = $name;
        $account->account = $name;
        $account->category_identifier = $category;
        $account->author_id = 1;
        $account->save();

        return $account;
    }

    private function createHistory( TransactionAccount $account, string $operation, float $value, string $createdAt ): void
    {
        TransactionHistory::withoutEvents( function () use ( $account, $operation, $value, $createdAt ): void {
            $history = new TransactionHistory;
            $history->operation = $operation;
            $history->transaction_account_id = $account->id;
            $history->name = 'Account summary test';
            $history->status = TransactionHistory::STATUS_ACTIVE;
            $history->value = $value;
            $history->trigger_date = $createdAt;
            $history->author_id = 1;
            $history->created_at = Carbon::parse( $createdAt );
            $history->updated_at = Carbon::parse( $createdAt );
            $history->save();
        } );
    }
}
