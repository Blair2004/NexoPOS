<?php

namespace Tests\Feature;

use App\Accounting\AccountingEventCatalog;
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

    public function test_it_creates_the_accounting_foundation_idempotently(): void
    {
        $service = app( TransactionService::class );

        $service->createAllSubAccounts();
        $service->createAllSubAccounts();

        $this->assertSame( 25, TransactionActionRule::query()->count() );
        $this->assertSame( 25, TransactionActionRule::query()->distinct()->count( 'on' ) );
        $this->assertSame( 24, TransactionAccount::query()->whereNotNull( 'system_identifier' )->count() );

        foreach ( app( AccountingEventCatalog::class )->all() as $event => $definition ) {
            $rule = TransactionActionRule::query()->where( 'on', $event )->with( 'lines' )->sole();

            $this->assertTrue( $rule->active, $definition['label'] );
            $this->assertGreaterThanOrEqual( 2, $rule->lines->count(), $definition['label'] );
        }
    }

    public function test_upgrade_restores_only_missing_default_groups(): void
    {
        $service = app( TransactionService::class );
        $service->createAllSubAccounts();

        $preservedRule = TransactionActionRule::query()->where( 'on', AccountingEventCatalog::ORDER_FINALIZED )->sole();
        TransactionActionRule::query()
            ->whereIn( 'on', [
                AccountingEventCatalog::ORDER_PAYMENT,
                AccountingEventCatalog::RETURN_DAMAGED,
            ] )
            ->delete();

        $service->upgradeAccountingFoundation();
        $service->upgradeAccountingFoundation();

        $this->assertSame( 25, TransactionActionRule::query()->count() );
        $this->assertSame( $preservedRule->id, TransactionActionRule::query()->where( 'on', AccountingEventCatalog::ORDER_FINALIZED )->sole()->id );
        $this->assertSame( 2, TransactionActionRule::query()->whereIn( 'on', [
            AccountingEventCatalog::ORDER_PAYMENT,
            AccountingEventCatalog::RETURN_DAMAGED,
        ] )->count() );
    }

    public function test_upgrade_preserves_custom_legacy_rules_while_merging_duplicates(): void
    {
        $service = app( TransactionService::class );
        $service->createAllSubAccounts();

        $cash = TransactionAccount::query()->where( 'system_identifier', 'cash' )->sole();
        $drawings = TransactionAccount::query()->where( 'system_identifier', 'owner_drawings' )->sole();
        $customRule = TransactionActionRule::query()->create( [
            'on' => TransactionActionRule::RULE_ORDER_PAID,
            'action' => 'decrease',
            'account_id' => $drawings->id,
            'do' => 'increase',
            'offset_account_id' => $cash->id,
            'active' => true,
        ] );

        $service->upgradeAccountingFoundation();

        $this->assertTrue( $customRule->fresh()->exists );
        $this->assertFalse( $customRule->fresh()->active );
        $activeRule = TransactionActionRule::query()
            ->where( 'on', TransactionActionRule::RULE_ORDER_PAID )
            ->where( 'active', true )
            ->with( 'lines' )
            ->sole();
        $this->assertTrue( $activeRule->lines->contains( fn( $line ) => $line->account_id === $drawings->id ) );
        $this->assertTrue( $activeRule->lines->contains( fn( $line ) => $line->account_id === $cash->id ) );
    }
}
