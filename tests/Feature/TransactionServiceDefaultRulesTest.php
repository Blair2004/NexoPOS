<?php

namespace Tests\Feature;

use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransactionServiceDefaultRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        TransactionActionRule::query()->delete();
        TransactionAccount::query()->delete();
    }

    public function test_it_creates_correct_and_unique_default_rules_idempotently(): void
    {
        $service = app( TransactionService::class );

        $service->createAllSubAccounts();
        $service->createAllSubAccounts();

        $this->assertSame( 14, TransactionActionRule::query()->count() );
        $this->assertSame( 14, TransactionActionRule::query()->distinct()->count( 'on' ) );

        $this->assertRule( 'procurement_unpaid', 'Inventory Account', 'increase', 'Procurement Payable', 'increase' );
        $this->assertRule( 'procurement_paid', 'Inventory Account', 'increase', 'Procurement Cash', 'decrease' );
        $this->assertRule( 'procurement_from_unpaid_to_paid', 'Procurement Payable', 'decrease', 'Procurement Cash', 'decrease' );
        $this->assertRule( 'order_unpaid', 'Receivables', 'increase', 'Sales Revenues', 'increase' );
        $this->assertRule( 'order_from_unpaid_to_paid', 'Sales', 'increase', 'Receivables', 'decrease' );
        $this->assertRule( 'order_paid', 'Sales', 'increase', 'Sales Revenues', 'increase' );
        $this->assertRule( 'order_partially_paid', 'Sales', 'increase', 'Sales Revenues', 'increase' );
        $this->assertRule( 'order_refunded', 'Sales Revenues', 'decrease', 'Sales', 'decrease' );
        $this->assertRule( 'order_partially_refunded', 'Sales Revenues', 'decrease', 'Sales', 'decrease' );
        $this->assertRule( 'order_cogs', 'Sales COGS', 'increase', 'Inventory Account', 'decrease' );
        $this->assertRule( 'product_damaged', 'Sales COGS', 'increase', 'Inventory Account', 'decrease' );
        $this->assertRule( 'product_returned', 'Sales COGS', 'decrease', 'Inventory Account', 'increase' );
        $this->assertRule( 'order_paid_voided', 'Sales Revenues', 'decrease', 'Sales', 'decrease' );
        $this->assertRule( 'order_unpaid_voided', 'Sales Revenues', 'decrease', 'Receivables', 'decrease' );
    }

    public function test_missing_rules_migration_adds_only_absent_defaults(): void
    {
        app( TransactionService::class )->createAllSubAccounts();

        TransactionActionRule::query()
            ->whereIn( 'on', [
                'order_partially_paid',
                'order_partially_refunded',
                'product_damaged',
                'product_returned',
            ] )
            ->delete();

        $migration = require base_path( 'database/migrations/update/2026_08_15_230257_add_missing_default_transaction_action_rules.php' );
        $migration->up();
        $migration->up();

        $this->assertSame( 14, TransactionActionRule::query()->count() );
        $this->assertRule( 'order_partially_paid', 'Sales', 'increase', 'Sales Revenues', 'increase' );
        $this->assertRule( 'order_partially_refunded', 'Sales Revenues', 'decrease', 'Sales', 'decrease' );
        $this->assertRule( 'product_damaged', 'Sales COGS', 'increase', 'Inventory Account', 'decrease' );
        $this->assertRule( 'product_returned', 'Sales COGS', 'decrease', 'Inventory Account', 'increase' );
    }

    public function test_migration_repairs_legacy_defaults_and_preserves_custom_rules(): void
    {
        app( TransactionService::class )->createAllSubAccounts();

        $paidOrder = TransactionActionRule::query()->where( 'on', 'order_paid' )->sole();
        $unpaidOrder = TransactionActionRule::query()->where( 'on', 'order_unpaid' )->sole();
        $unpaidToPaidOrder = TransactionActionRule::query()->where( 'on', 'order_from_unpaid_to_paid' )->sole();
        $paidVoidedOrder = TransactionActionRule::query()->where( 'on', 'order_paid_voided' )->sole();
        $procurementPaid = TransactionActionRule::query()->where( 'on', 'procurement_paid' )->sole();
        $expenseCash = TransactionAccount::query()->where( 'name', 'Expenses Cash' )->sole();

        $unpaidToPaidOrder->update( [ 'action' => 'decrease', 'do' => 'increase' ] );
        $paidOrder->update( [
            'offset_account_id' => $unpaidOrder->account_id,
            'do' => 'decrease',
        ] );
        $paidVoidedOrder->update( [
            'account_id' => $paidOrder->account_id,
            'action' => 'increase',
            'offset_account_id' => $paidOrder->account_id,
            'do' => 'decrease',
        ] );

        TransactionActionRule::query()->create( [
            'on' => 'procurement_paid',
            'action' => 'increase',
            'account_id' => $expenseCash->id,
            'do' => 'decrease',
            'offset_account_id' => $procurementPaid->offset_account_id,
        ] );
        TransactionActionRule::query()->create( [
            'on' => 'order_unpaid',
            'action' => 'increase',
            'account_id' => $expenseCash->id,
            'do' => 'decrease',
            'offset_account_id' => $procurementPaid->account_id,
        ] );
        $customRule = TransactionActionRule::query()->create( [
            'on' => 'order_paid',
            'action' => 'decrease',
            'account_id' => $expenseCash->id,
            'do' => 'increase',
            'offset_account_id' => $expenseCash->id,
        ] );

        $migration = require base_path( 'database/migrations/update/2026_08_15_221945_repair_default_transaction_action_rules.php' );
        $migration->up();
        $migration->up();

        $this->assertRule( 'order_from_unpaid_to_paid', 'Sales', 'increase', 'Receivables', 'decrease' );
        $this->assertRule( 'order_paid', 'Sales', 'increase', 'Sales Revenues', 'increase' );
        $this->assertRule( 'order_paid_voided', 'Sales Revenues', 'decrease', 'Sales', 'decrease' );
        $this->assertSame( 1, TransactionActionRule::query()->where( 'on', 'procurement_paid' )->count() );
        $this->assertSame( 1, TransactionActionRule::query()->where( 'on', 'order_unpaid' )->count() );
        $this->assertTrue( $customRule->fresh()->exists );
    }

    private function assertRule( string $on, string $accountName, string $action, string $offsetAccountName, string $do ): void
    {
        $rule = TransactionActionRule::query()->where( 'on', $on )->orderBy( 'id' )->firstOrFail();

        $this->assertSame( $accountName, TransactionAccount::query()->findOrFail( $rule->account_id )->name );
        $this->assertSame( $action, $rule->action );
        $this->assertSame( $offsetAccountName, TransactionAccount::query()->findOrFail( $rule->offset_account_id )->name );
        $this->assertSame( $do, $rule->do );
    }
}
