<?php

namespace Tests\Feature;

use App\Accounting\AccountingEventCatalog;
use App\Accounting\AccountingRuleValidator;
use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingGroupedRuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_chart_and_grouped_defaults_are_exact_and_idempotent(): void
    {
        $service = app( TransactionService::class );

        $service->upgradeAccountingFoundation();
        $service->upgradeAccountingFoundation();

        $expected = [
            'fixed_assets' => '1001',
            'current_assets' => '1002',
            'inventory' => '1003',
            'cash' => '1004',
            'bank' => '1005',
            'accounts_receivable' => '1006',
            'payment_clearing' => '1007',
            'current_liabilities' => '2001',
            'accounts_payable' => '2002',
            'sales_tax_payable' => '2003',
            'customer_deposits' => '2004',
            'owner_capital' => '3001',
            'owner_drawings' => '3002',
            'retained_earnings' => '3003',
            'sales_revenue' => '4001',
            'sales_returns' => '4002',
            'cogs' => '5001',
            'operating_expenses' => '5100',
            'rent' => '5101',
            'salaries_wages' => '5102',
            'utilities' => '5103',
            'maintenance' => '5104',
            'other_expenses' => '5105',
            'inventory_variance' => '5106',
        ];

        $accounts = TransactionAccount::query()->whereNotNull( 'system_identifier' )->get()->keyBy( 'system_identifier' );

        $this->assertCount( count( $expected ), $accounts );

        foreach ( $expected as $identifier => $code ) {
            $this->assertSame( $code, $accounts->get( $identifier )->account );
        }

        foreach ( [ 'rent', 'salaries_wages', 'utilities', 'maintenance', 'other_expenses', 'inventory_variance' ] as $child ) {
            $this->assertSame( $accounts->get( 'operating_expenses' )->id, $accounts->get( $child )->sub_category_id );
        }

        foreach ( app( AccountingEventCatalog::class )->all() as $event => $definition ) {
            $group = TransactionActionRule::query()->where( 'on', $event )->where( 'active', true )->with( 'lines' )->sole();
            $this->assertGreaterThanOrEqual( 2, $group->lines->count(), $definition['label'] );
        }
    }

    public function test_only_untouched_legacy_accounts_are_normalized(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $inventory = TransactionAccount::query()->where( 'system_identifier', 'inventory' )->sole();
        $inventory->forceFill( [
            'system_identifier' => null,
            'account' => '1003-assets-inventory-account',
            'name' => 'Inventory Account',
        ] )->save();

        app( TransactionService::class )->upgradeAccountingFoundation();

        $this->assertSame( $inventory->id, TransactionAccount::query()->where( 'system_identifier', 'inventory' )->sole()->id );
        $this->assertSame( '1003', $inventory->fresh()->account );
    }

    public function test_customized_legacy_account_codes_are_preserved(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();
        $inventory = TransactionAccount::query()->where( 'system_identifier', 'inventory' )->sole();
        $inventory->forceFill( [
            'system_identifier' => null,
            'account' => 'CUSTOM-INVENTORY',
            'name' => 'Inventory Account',
        ] )->save();

        app( TransactionService::class )->upgradeAccountingFoundation();

        $this->assertNull( $inventory->fresh()->system_identifier );
        $this->assertSame( 'CUSTOM-INVENTORY', $inventory->fresh()->account );
        $this->assertNotSame( $inventory->id, TransactionAccount::query()->where( 'system_identifier', 'inventory' )->sole()->id );
    }

    public function test_group_save_is_atomic_and_symbolically_validated(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();

        $inventory = TransactionAccount::query()->where( 'system_identifier', 'inventory' )->sole();
        $variance = TransactionAccount::query()->where( 'system_identifier', 'inventory_variance' )->sole();
        $payload = [
            'on' => AccountingEventCatalog::ADJUSTMENT_POSITIVE,
            'active' => true,
            'lines' => [
                [ 'account_id' => $inventory->id, 'effect' => 'increase', 'amount_source' => 'adjustment_cost' ],
                [ 'account_id' => $variance->id, 'effect' => 'decrease', 'amount_source' => 'adjustment_cost' ],
            ],
        ];

        $response = app( TransactionService::class )->saveTransactionRule( $payload );
        $saved = $response['data']['rule'];

        $this->assertCount( 2, $saved->lines );
        $this->assertSame( [ 0, 1 ], $saved->lines->pluck( 'display_order' )->all() );
        $this->assertSame( 1, TransactionActionRule::query()->where( 'on', AccountingEventCatalog::ADJUSTMENT_POSITIVE )->where( 'active', true )->count() );

        $originalLineIds = $saved->lines->pluck( 'id' )->all();
        $reorderedPayload = [
            'id' => $saved->id,
            'on' => $saved->on,
            'active' => true,
            'lines' => $saved->lines->reverse()->values()->map( fn( $line ) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'effect' => $line->effect,
                'amount_source' => $line->amount_source,
            ] )->all(),
        ];
        $reordered = app( TransactionService::class )->saveTransactionRule( $reorderedPayload )['data']['rule'];

        $this->assertSame( array_reverse( $originalLineIds ), $reordered->lines->pluck( 'id' )->all() );
        $this->assertSame( [ 0, 1 ], $reordered->lines->pluck( 'display_order' )->all() );

        $payload['lines'][1]['effect'] = 'increase';

        $this->expectException( ValidationException::class );
        app( AccountingRuleValidator::class )->validate( $payload );
    }

    public function test_legacy_duplicate_rows_are_merged_without_losing_custom_lines_or_ids(): void
    {
        app( TransactionService::class )->upgradeAccountingFoundation();

        $cash = TransactionAccount::query()->where( 'system_identifier', 'cash' )->sole();
        $revenue = TransactionAccount::query()->where( 'system_identifier', 'sales_revenue' )->sole();
        $duplicate = TransactionActionRule::query()->create( [
            'on' => TransactionActionRule::RULE_ORDER_PAID,
            'action' => 'increase',
            'account_id' => $cash->id,
            'do' => 'increase',
            'offset_account_id' => $revenue->id,
            'active' => true,
        ] );

        app( TransactionService::class )->upgradeAccountingFoundation();

        $this->assertTrue( $duplicate->fresh()->exists );
        $this->assertFalse( $duplicate->fresh()->active );

        $active = TransactionActionRule::query()
            ->where( 'on', TransactionActionRule::RULE_ORDER_PAID )
            ->where( 'active', true )
            ->with( 'lines' )
            ->sole();

        $this->assertGreaterThanOrEqual( 4, $active->lines->count() );
    }
}
