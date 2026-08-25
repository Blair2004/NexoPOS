<?php

namespace App\Accounting;

use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AccountingRuleValidator
{
    public function __construct( private AccountingEventCatalog $catalog ) {}

    /**
     * @param array{on: string, lines: array<int, array<string, mixed>>} $group
     */
    public function validate( array $group ): void
    {
        $event = $this->catalog->get( $group['on'] );

        if ( $event === null && in_array( $group['on'], TransactionActionRule::LEGACY_EVENTS, true ) ) {
            $event = [
                'label' => $group['on'],
                'amount_sources' => [ 'total' => __( 'Transaction Total' ) ],
                'dynamic_account_roles' => [],
            ];
        }

        if ( $event === null ) {
            throw ValidationException::withMessages( [ 'rule.on' => __( 'The selected accounting event is not supported.' ) ] );
        }

        $lines = collect( $group['lines'] ?? [] );

        if ( $lines->count() < 2 ) {
            throw ValidationException::withMessages( [ 'rule.lines' => __( 'An accounting rule requires at least two actions.' ) ] );
        }

        $accounts = TransactionAccount::query()
            ->whereIn( 'id', $lines->pluck( 'account_id' )->filter()->unique() )
            ->get()
            ->keyBy( 'id' );

        foreach ( $lines as $index => $line ) {
            $hasAccount = ! empty( $line['account_id'] );
            $hasRole = ! empty( $line['dynamic_account_role'] );

            if ( $hasAccount === $hasRole ) {
                throw ValidationException::withMessages( [ "rule.lines.{$index}.account_id" => __( 'Choose either an account or a dynamic account role.' ) ] );
            }

            if ( $hasAccount && ! $accounts->has( (int) $line['account_id'] ) ) {
                throw ValidationException::withMessages( [ "rule.lines.{$index}.account_id" => __( 'The selected account does not exist.' ) ] );
            }

            if ( $hasRole && ! isset( $event['dynamic_account_roles'][ $line['dynamic_account_role'] ] ) ) {
                throw ValidationException::withMessages( [ "rule.lines.{$index}.dynamic_account_role" => __( 'The selected dynamic account role is not supported for this event.' ) ] );
            }

            if ( ! isset( $event['amount_sources'][ $line['amount_source'] ?? '' ] ) ) {
                throw ValidationException::withMessages( [ "rule.lines.{$index}.amount_source" => __( 'The selected amount source is not supported for this event.' ) ] );
            }
        }

        if ( ! $this->isSymbolicallyBalanced( $group['on'], $lines, $accounts, $event['dynamic_account_roles'] ) ) {
            throw ValidationException::withMessages( [ 'rule.lines' => __( 'The accounting actions are not symbolically balanced.' ) ] );
        }
    }

    /**
     * @param Collection<int, array<string, mixed>>                  $lines
     * @param Collection<int, TransactionAccount>                    $accounts
     * @param array<string, array{label: string, operation: string}> $roles
     */
    private function isSymbolicallyBalanced( string $event, Collection $lines, Collection $accounts, array $roles ): bool
    {
        $operations = $lines->map( function ( array $line ) use ( $accounts, $roles ): array {
            $operation = ! empty( $line['dynamic_account_role'] )
                ? $roles[ $line['dynamic_account_role'] ]['operation']
                : $this->catalog->operation( $accounts->get( (int) $line['account_id'] )->category_identifier, $line['effect'] );

            return [ 'operation' => $operation, 'source' => $line['amount_source'] ];
        } );

        if ( $event === AccountingEventCatalog::ORDER_FINALIZED ) {
            return $this->sameCount( $operations, 'debit', 'total', 1 )
                && $this->sameCount( $operations, 'credit', 'net_sale', 1 )
                && $this->sameCount( $operations, 'credit', 'tax', 1 )
                && $this->paired( $operations, 'cogs' );
        }

        if ( $event === AccountingEventCatalog::ORDER_REFUND ) {
            return $this->sameCount( $operations, 'debit', 'net_refund', 1 )
                && $this->sameCount( $operations, 'debit', 'refunded_tax', 1 )
                && $this->sameCount( $operations, 'credit', 'refund_unpaid', 1 )
                && $this->sameCount( $operations, 'credit', 'refund_paid', 1 );
        }

        return $operations->groupBy( 'source' )->every( fn( Collection $sourceLines ) => $sourceLines->where( 'operation', 'debit' )->count() === $sourceLines->where( 'operation', 'credit' )->count() );
    }

    private function paired( Collection $operations, string $source ): bool
    {
        return $operations->where( 'source', $source )->where( 'operation', 'debit' )->count()
            === $operations->where( 'source', $source )->where( 'operation', 'credit' )->count();
    }

    private function sameCount( Collection $operations, string $operation, string $source, int $count ): bool
    {
        return $operations->where( 'operation', $operation )->where( 'source', $source )->count() === $count;
    }
}
