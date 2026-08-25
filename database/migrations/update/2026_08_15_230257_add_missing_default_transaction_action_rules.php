<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'nexopos_transactions_actions_rules';

    /**
     * Add missing default rules without replacing configured rules.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( self::TABLE ) ) {
            return;
        }

        DB::transaction( function (): void {
            $paidOrder = $this->firstRule( 'order_paid' );
            $refundedOrder = $this->firstRule( 'order_refunded' );
            $cogsOrder = $this->firstRule( 'order_cogs' );

            if ( $paidOrder !== null ) {
                $this->insertRuleIfMissing( 'order_partially_paid', [
                    'action' => 'increase',
                    'account_id' => $paidOrder->account_id,
                    'do' => 'increase',
                    'offset_account_id' => $paidOrder->offset_account_id,
                ] );
            }

            if ( $refundedOrder !== null ) {
                $this->insertRuleIfMissing( 'order_partially_refunded', [
                    'action' => 'decrease',
                    'account_id' => $refundedOrder->account_id,
                    'do' => 'decrease',
                    'offset_account_id' => $refundedOrder->offset_account_id,
                ] );
            }

            if ( $cogsOrder !== null ) {
                $this->insertRuleIfMissing( 'product_damaged', [
                    'action' => 'increase',
                    'account_id' => $cogsOrder->account_id,
                    'do' => 'decrease',
                    'offset_account_id' => $cogsOrder->offset_account_id,
                ] );
                $this->insertRuleIfMissing( 'product_returned', [
                    'action' => 'decrease',
                    'account_id' => $cogsOrder->account_id,
                    'do' => 'increase',
                    'offset_account_id' => $cogsOrder->offset_account_id,
                ] );
            }
        } );
    }

    /**
     * The migration only adds absent defaults and is safe to replay.
     */
    public function down(): void
    {
        // Do not remove rules that may have been customized after migration.
    }

    private function firstRule( string $on ): ?object
    {
        return DB::table( self::TABLE )
            ->where( 'on', $on )
            ->orderBy( 'id' )
            ->first();
    }

    /**
     * @param array{action: string, account_id: int, do: string, offset_account_id: int} $rule
     */
    private function insertRuleIfMissing( string $on, array $rule ): void
    {
        if ( $this->firstRule( $on ) !== null ) {
            return;
        }

        DB::table( self::TABLE )->insert( [
            'on' => $on,
            ...$rule,
            'locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ] );
    }
};
