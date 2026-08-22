<?php

namespace Tests\Feature;

use App\Accounting\AccountingEventCatalog;
use App\Exceptions\NotAllowedException;
use App\Models\AccountingJournal;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\TransactionActionRule;
use App\Models\TransactionHistory;
use App\Services\AccountingJournalService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountingJournalServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_journal_posting_is_balanced_and_idempotent(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $service = app( AccountingJournalService::class );

        $first = $service->post(
            AccountingEventCatalog::WALLET_ADDITION,
            'test-wallet',
            42,
            'Opening wallet deposit',
            [ 'wallet_amount' => 125.50 ],
            authorId: ns()->getValidAuthor(),
        );
        $second = $service->post(
            AccountingEventCatalog::WALLET_ADDITION,
            'test-wallet',
            42,
            'Repeated listener',
            [ 'wallet_amount' => 125.50 ],
            authorId: ns()->getValidAuthor(),
        );

        $this->assertSame( $first->id, $second->id );
        $this->assertSame( 1, AccountingJournal::query()->where( 'source_type', 'test-wallet' )->where( 'source_id', '42' )->count() );
        $this->assertCount( 2, $first->lines );
        $this->assertSame( 125.5, (float) $first->lines->where( 'operation', TransactionHistory::OPERATION_DEBIT )->sum( 'value' ) );
        $this->assertSame( 125.5, (float) $first->lines->where( 'operation', TransactionHistory::OPERATION_CREDIT )->sum( 'value' ) );
    }

    public function test_runtime_rejects_an_unbalanced_computed_journal_atomically(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $rule = TransactionActionRule::query()->where( 'on', AccountingEventCatalog::WALLET_ADDITION )->with( 'lines' )->sole();
        $rule->lines->last()->update( [ 'effect' => 'decrease' ] );

        try {
            app( AccountingJournalService::class )->post(
                AccountingEventCatalog::WALLET_ADDITION,
                'test-wallet',
                99,
                'Broken wallet deposit',
                [ 'wallet_amount' => 50 ],
                authorId: ns()->getValidAuthor(),
            );

            $this->fail( 'An unbalanced journal should have been rejected.' );
        } catch ( NotAllowedException ) {
            $this->assertFalse( AccountingJournal::query()->where( 'source_type', 'test-wallet' )->where( 'source_id', '99' )->exists() );
        }
    }

    public function test_opening_balance_is_balanced_and_idempotent(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        AccountingJournal::query()
            ->where( 'source_type', 'accounting-cutover' )
            ->where( 'source_id', '6.2.2' )
            ->get()
            ->each( function ( AccountingJournal $journal ): void {
                $journal->lines()->delete();
                $journal->delete();
            } );
        $unitQuantity = $this->createUnitQuantity();
        $unitQuantity->forceFill( [ 'quantity' => 3, 'cogs' => 4 ] )->save();

        $service = app( AccountingJournalService::class );
        $first = $service->postOpeningBalance();
        $second = $service->postOpeningBalance();

        $this->assertNotNull( $first );
        $this->assertSame( $first->id, $second->id );
        $this->assertSame(
            (float) $first->lines->where( 'operation', TransactionHistory::OPERATION_DEBIT )->sum( 'value' ),
            (float) $first->lines->where( 'operation', TransactionHistory::OPERATION_CREDIT )->sum( 'value' ),
        );
    }

    public function test_manual_stock_adjustments_post_at_cogs_and_are_idempotent(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $unitQuantity = $this->createUnitQuantity();
        $unitQuantity->forceFill( [ 'cogs' => 7.5 ] )->save();
        $history = new ProductHistory;
        $history->id = 987654;
        $history->product_id = $unitQuantity->product_id;
        $history->unit_id = $unitQuantity->unit_id;
        $history->quantity = 2;
        $history->operation_type = ProductHistory::ACTION_LOST;
        $history->author_id = ns()->getValidAuthor();

        $service = app( AccountingJournalService::class );
        $first = $service->postStockAdjustment( $history );
        $second = $service->postStockAdjustment( $history );

        $this->assertSame( $first->id, $second->id );
        $this->assertSame( 15.0, (float) $first->lines->sum( 'value' ) / 2 );
    }

    public function test_order_wallet_history_is_not_posted_twice(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $history = new CustomerAccountHistory;
        $history->id = 765432;
        $history->order_id = 123;
        $history->operation = CustomerAccountHistory::OPERATION_PAYMENT;
        $history->amount = 10;
        $history->author_id = ns()->getValidAuthor();

        $this->assertNull( app( AccountingJournalService::class )->postWallet( $history ) );
        $this->assertFalse( AccountingJournal::query()
            ->where( 'source_type', CustomerAccountHistory::class )
            ->where( 'source_id', (string) $history->id )
            ->exists() );
    }

    public function test_void_reverses_all_posted_order_journal_lines_once(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $order = $this->createOrder();
        $service = app( AccountingJournalService::class );
        $original = $service->post(
            AccountingEventCatalog::WALLET_ADDITION,
            Order::class,
            $order->id,
            'Order-linked journal',
            [ 'wallet_amount' => 20 ],
            sourceColumns: [ 'order_id' => $order->id ],
            authorId: ns()->getValidAuthor(),
        );

        $void = $service->postOrderVoid( $order );
        $repeated = $service->postOrderVoid( $order );

        $this->assertSame( AccountingJournal::STATUS_REVERSED, $original->fresh()->status );
        $this->assertTrue( $order->accountingJournals()->whereKey( $original->id )->exists() );
        $this->assertSame( $void->id, $repeated->id );
        $this->assertSame(
            (float) $original->lines->where( 'operation', TransactionHistory::OPERATION_DEBIT )->sum( 'value' ),
            (float) $void->lines->where( 'operation', TransactionHistory::OPERATION_CREDIT )->sum( 'value' ),
        );
    }

    public function test_zero_and_negative_journal_values_do_not_post(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();

        $this->expectException( NotAllowedException::class );

        app( AccountingJournalService::class )->post(
            AccountingEventCatalog::WALLET_ADDITION,
            'test-wallet',
            100,
            'Negative wallet deposit',
            [ 'wallet_amount' => -1 ],
            authorId: ns()->getValidAuthor(),
        );
    }

    private function createUnitQuantity(): ProductUnitQuantity
    {
        return ProductUnitQuantity::withoutEvents( function (): ProductUnitQuantity {
            $unitQuantity = new ProductUnitQuantity;
            $unitQuantity->forceFill( [
                'product_id' => 987654,
                'unit_id' => 987654,
                'quantity' => 0,
                'cogs' => 0,
            ] );
            $unitQuantity->save();

            return $unitQuantity;
        } );
    }

    private function createOrder(): Order
    {
        return Order::withoutEvents( function (): Order {
            $order = new Order;
            $order->forceFill( [
                'code' => 'TEST-VOID',
                'type' => 'takeaway',
                'payment_status' => Order::PAYMENT_PAID,
                'customer_id' => 1,
                'author_id' => ns()->getValidAuthor() ?: 1,
                'created_at' => ns()->date->toDateTimeString(),
                'updated_at' => ns()->date->toDateTimeString(),
            ] );
            $order->save();

            return $order;
        } );
    }
}
