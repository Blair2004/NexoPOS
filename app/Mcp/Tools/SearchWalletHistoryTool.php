<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\CustomerAccountHistory;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchWalletHistoryTool extends Tool
{
    protected string $name = 'search_wallet_history';

    protected string $description = 'Search customer wallet (account) history. Use this to lookup wallet top-ups, payments, or balance adjustments for a specific customer or transaction type over a period.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'customer_id' => $schema->integer()
                ->description( 'Filter by specific customer ID.' )
                ->nullable(),
            'operation' => $schema->string()
                ->description( 'Filter by operation type (e.g. "add", "subtract").' )
                ->nullable(),
            'date_start' => $schema->string()
                ->description( 'Start date (YYYY-MM-DD).' )
                ->nullable(),
            'date_end' => $schema->string()
                ->description( 'End date (YYYY-MM-DD).' )
                ->nullable(),
            'limit' => $schema->integer()
                ->description( 'Max number of transactions to return.' )
                ->default( 15 )
                ->min( 1 )
                ->max( 50 ),
        ];
    }

    public function handle( Request $request ): Response
    {
        try {
            $query = CustomerAccountHistory::query()
                ->with( ['author', 'customer', 'order'] );

            $customerId = $request->get( 'customer_id' );
            if ( ! is_null( $customerId ) ) {
                $query->where( 'customer_id', $customerId );
            }

            $operation = $request->get( 'operation' );
            if ( ! empty( $operation ) ) {
                $query->where( 'operation', $operation );
            }

            $dateStart = $request->get( 'date_start' );
            if ( ! empty( $dateStart ) ) {
                $query->where( 'created_at', '>=', Carbon::parse( $dateStart ) );
            }

            $dateEnd = $request->get( 'date_end' );
            if ( ! empty( $dateEnd ) ) {
                $query->where( 'created_at', '<=', Carbon::parse( $dateEnd )->endOfDay() );
            }

            $limit = (int) $request->get( 'limit', 15 );
            $history = $query->orderBy( 'id', 'desc' )->limit( $limit )->get();

            return Response::json( $history->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
