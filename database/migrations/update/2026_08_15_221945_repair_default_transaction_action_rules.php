<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'nexopos_transactions_actions_rules';

    /**
     * Repair only rules that still match the legacy default relationships.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( self::TABLE ) ) {
            return;
        }

        DB::transaction( function (): void {
            $unpaidOrder = $this->firstRule( 'order_unpaid' );
            $paidOrder = $this->firstRule( 'order_paid' );

            if ( $unpaidOrder !== null && $paidOrder !== null ) {
                $this->repairRule(
                    on: 'order_from_unpaid_to_paid',
                    legacy: [
                        'account_id' => $paidOrder->account_id,
                        'action' => 'decrease',
                        'offset_account_id' => $unpaidOrder->account_id,
                        'do' => 'increase',
                    ],
                    corrected: [
                        'account_id' => $paidOrder->account_id,
                        'action' => 'increase',
                        'offset_account_id' => $unpaidOrder->account_id,
                        'do' => 'decrease',
                    ]
                );

                $this->repairRule(
                    on: 'order_paid',
                    legacy: [
                        'account_id' => $paidOrder->account_id,
                        'action' => 'increase',
                        'offset_account_id' => $unpaidOrder->account_id,
                        'do' => 'decrease',
                    ],
                    corrected: [
                        'account_id' => $paidOrder->account_id,
                        'action' => 'increase',
                        'offset_account_id' => $unpaidOrder->offset_account_id,
                        'do' => 'increase',
                    ]
                );

                $this->repairRule(
                    on: 'order_paid_voided',
                    legacy: [
                        'account_id' => $paidOrder->account_id,
                        'action' => 'increase',
                        'offset_account_id' => $paidOrder->account_id,
                        'do' => 'decrease',
                    ],
                    corrected: [
                        'account_id' => $unpaidOrder->offset_account_id,
                        'action' => 'decrease',
                        'offset_account_id' => $paidOrder->account_id,
                        'do' => 'decrease',
                    ]
                );
            }

            $this->removeLegacyDuplicateRules();
        } );
    }

    /**
     * This corrective data migration is intentionally irreversible.
     */
    public function down(): void
    {
        // A rollback must not restore invalid accounting rules.
    }

    private function firstRule( string $on ): ?object
    {
        return DB::table( self::TABLE )
            ->where( 'on', $on )
            ->orderBy( 'id' )
            ->first();
    }

    /**
     * @param array{account_id: int, action: string, offset_account_id: int, do: string} $legacy
     * @param array{account_id: int, action: string, offset_account_id: int, do: string} $corrected
     */
    private function repairRule( string $on, array $legacy, array $corrected ): void
    {
        $rule = $this->firstRule( $on );

        if ( $rule === null ) {
            return;
        }

        foreach ( $legacy as $column => $value ) {
            if ( $rule->{$column} !== $value ) {
                return;
            }
        }

        DB::table( self::TABLE )
            ->where( 'id', $rule->id )
            ->update( [ ...$corrected, 'updated_at' => now() ] );
    }

    private function removeLegacyDuplicateRules(): void
    {
        $procurementRules = DB::table( self::TABLE )
            ->where( 'on', 'procurement_paid' )
            ->orderBy( 'id' )
            ->get();
        $unpaidOrderRules = DB::table( self::TABLE )
            ->where( 'on', 'order_unpaid' )
            ->orderBy( 'id' )
            ->get();

        $procurementRule = $procurementRules->first();

        if ( $procurementRule === null || $unpaidOrderRules->isEmpty() ) {
            return;
        }

        foreach ( $procurementRules->skip( 1 ) as $duplicateProcurementRule ) {
            foreach ( $unpaidOrderRules->skip( 1 ) as $duplicateUnpaidOrderRule ) {
                $isLegacyPair = $duplicateProcurementRule->action === 'increase'
                    && $duplicateProcurementRule->do === 'decrease'
                    && $duplicateProcurementRule->offset_account_id === $procurementRule->offset_account_id
                    && $duplicateUnpaidOrderRule->action === 'increase'
                    && $duplicateUnpaidOrderRule->do === 'decrease'
                    && $duplicateUnpaidOrderRule->account_id === $duplicateProcurementRule->account_id
                    && $duplicateUnpaidOrderRule->offset_account_id === $procurementRule->account_id;

                if ( $isLegacyPair ) {
                    DB::table( self::TABLE )
                        ->whereIn( 'id', [ $duplicateProcurementRule->id, $duplicateUnpaidOrderRule->id ] )
                        ->delete();

                    return;
                }
            }
        }
    }
};
