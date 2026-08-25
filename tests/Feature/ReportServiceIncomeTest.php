<?php

namespace Tests\Feature;

use App\Models\DashboardDay;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportServiceIncomeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_income_and_expenses_are_limited_to_their_account_categories(): void
    {
        $revenueAccount = $this->createAccount( 'Sales Revenues', 'revenues' );
        $expenseAccount = $this->createAccount( 'Sales COGS', 'expenses' );
        $assetAccount = $this->createAccount( 'Sales', 'assets' );
        $rangeStart = '2026-08-15 00:00:00';
        $rangeEnd = '2026-08-15 23:59:59';

        $this->createHistory( $revenueAccount, TransactionHistory::OPERATION_CREDIT, 100, '2026-08-15 10:00:00' );
        $this->createHistory( $assetAccount, TransactionHistory::OPERATION_DEBIT, 100, '2026-08-15 10:00:00' );
        $this->createHistory( $expenseAccount, TransactionHistory::OPERATION_DEBIT, 30, '2026-08-15 11:00:00' );
        $this->createHistory( $assetAccount, TransactionHistory::OPERATION_CREDIT, 30, '2026-08-15 11:00:00' );
        $this->createHistory( $revenueAccount, TransactionHistory::OPERATION_CREDIT, 500, '2026-08-14 10:00:00' );
        $this->createHistory( $expenseAccount, TransactionHistory::OPERATION_DEBIT, 500, '2026-08-15 12:00:00', TransactionHistory::STATUS_PENDING );

        $service = app( ReportService::class );
        $this->setReportRange( $service, $rangeStart, $rangeEnd );

        $previousReport = new DashboardDay;
        $previousReport->total_income = 10;
        $previousReport->total_expenses = 5;
        $todayReport = new DashboardDay;

        $service->computeIncome( $previousReport, $todayReport );

        $this->assertSame( 100.0, (float) $todayReport->day_income );
        $this->assertSame( 30.0, (float) $todayReport->day_expenses );
        $this->assertSame( 110.0, (float) $todayReport->total_income );
        $this->assertSame( 35.0, (float) $todayReport->total_expenses );
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

    private function createHistory( TransactionAccount $account, string $operation, float $value, string $createdAt, string $status = TransactionHistory::STATUS_ACTIVE ): void
    {
        TransactionHistory::withoutEvents( function () use ( $account, $operation, $value, $createdAt, $status ): void {
            $history = new TransactionHistory;
            $history->operation = $operation;
            $history->transaction_account_id = $account->id;
            $history->name = 'Report test';
            $history->status = $status;
            $history->value = $value;
            $history->trigger_date = $createdAt;
            $history->author_id = 1;
            $history->created_at = Carbon::parse( $createdAt );
            $history->updated_at = Carbon::parse( $createdAt );
            $history->save();
        } );
    }

    private function setReportRange( ReportService $service, string $rangeStart, string $rangeEnd ): void
    {
        $reflection = new \ReflectionClass( $service );
        $reflection->getProperty( 'dayStarts' )->setValue( $service, $rangeStart );
        $reflection->getProperty( 'dayEnds' )->setValue( $service, $rangeEnd );
    }
}
