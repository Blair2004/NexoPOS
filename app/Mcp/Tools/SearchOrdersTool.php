<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchOrdersTool extends Tool
{
    protected string $name = 'search_orders';

    protected string $description = 'Search and list orders with advanced filtering. Useful to find orders created in a date range, placed by a specific user or customer, having specific payment statuses, or check if they have installments.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'date_start' => $schema->string()
                ->description( 'Optional start date (YYYY-MM-DD format) to filter orders.' )
                ->nullable(),
            'date_end' => $schema->string()
                ->description( 'Optional end date (YYYY-MM-DD format) to filter orders.' )
                ->nullable(),
            'author_id' => $schema->integer()
                ->description( 'User ID to filter orders placed by a specific author.' )
                ->nullable(),
            'customer_id' => $schema->integer()
                ->description( 'Customer ID to filter orders for a specific customer.' )
                ->nullable(),
            'payment_status' => $schema->string()
                ->description( 'Filter orders by payment status (e.g., unpaid, paid, partially_paid, hold).' )
                ->nullable(),
            'has_installments' => $schema->boolean()
                ->description( 'Filter orders based on whether they have installments (true = with installments, false = without installments).' )
                ->nullable(),
            'limit' => $schema->integer()
                ->description( 'Max number of orders to return.' )
                ->default( 10 )
                ->min( 1 )
                ->max( 50 ),
        ];
    }

    public function handle( Request $request ): Response
    {
        try {
            $query = Order::query()->with( ['user', 'customer', 'instalments'] );

            $dateStart = $request->get( 'date_start' );
            if ( ! empty( $dateStart ) ) {
                $query->where( 'created_at', '>=', Carbon::parse( $dateStart ) );
            }

            $dateEnd = $request->get( 'date_end' );
            if ( ! empty( $dateEnd ) ) {
                $query->where( 'created_at', '<=', Carbon::parse( $dateEnd )->endOfDay() );
            }

            $authorId = $request->get( 'author_id' );
            if ( ! is_null( $authorId ) ) {
                $query->where( 'author', $authorId );
            }

            $customerId = $request->get( 'customer_id' );
            if ( ! is_null( $customerId ) ) {
                $query->where( 'customer_id', $customerId );
            }

            $paymentStatus = $request->get( 'payment_status' );
            if ( ! empty( $paymentStatus ) ) {
                $query->where( 'payment_status', $paymentStatus );
            }

            $hasInstallments = $request->get( 'has_installments' );
            if ( ! is_null( $hasInstallments ) ) {
                if ( $hasInstallments ) {
                    $query->has( 'instalments' );
                } else {
                    $query->doesntHave( 'instalments' );
                }
            }

            $limit = (int) $request->get( 'limit', 10 );
            $orders = $query->orderBy( 'id', 'desc' )->limit( $limit )->get();

            return Response::json( $orders->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
