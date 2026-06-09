<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\OrderProduct;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchProductSalesTool extends Tool
{
    protected string $name = 'search_product_sales';

    protected string $description = 'Search product sales metrics, identify most purchased products, top returned products, and overall sales volume during a specific period.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'date_start' => $schema->string()
                ->description('Start date (YYYY-MM-DD). If omitted, aggregates all time.')
                ->nullable(),
            'date_end' => $schema->string()
                ->description('End date (YYYY-MM-DD).')
                ->nullable(),
            'order_type' => $schema->string()
                ->description('Optional order type to filter sales (e.g. pos, delivery).')
                ->nullable(),
            'metric' => $schema->string()
                ->description('Metric to return: "volume" (total sales), "top_purchased", or "top_returned". defaults to top_purchased')
                ->default('top_purchased'),
            'limit' => $schema->integer()
                ->description('Limit for lists like top_purchased or top_returned.')
                ->default(10)
                ->min(1)
                ->max(50),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $query = OrderProduct::query()
                ->join((new \App\Models\Order)->getTable(), (new \App\Models\Order)->getTable() . '.id', '=', (new \App\Models\OrderProduct)->getTable() . '.order_id')
                ->join((new \App\Models\Product)->getTable(), (new \App\Models\Product)->getTable() . '.id', '=', (new \App\Models\OrderProduct)->getTable() . '.product_id');

            $dateStart = $request->get('date_start');
            if (!empty($dateStart)) {
                $query->where((new \App\Models\Order)->getTable() . '.created_at', '>=', Carbon::parse($dateStart));
            }

            $dateEnd = $request->get('date_end');
            if (!empty($dateEnd)) {
                $query->where((new \App\Models\Order)->getTable() . '.created_at', '<=', Carbon::parse($dateEnd)->endOfDay());
            }

            $orderType = $request->get('order_type');
            if (!empty($orderType)) {
                $query->where((new \App\Models\Order)->getTable() . '.type', $orderType);
            }

            $metric = $request->get('metric', 'top_purchased');
            $limit = (int) $request->get('limit', 10);

            if ($metric === 'top_purchased') {
                $results = $query->select(
                    (new \App\Models\Product)->getTable() . '.id',
                    (new \App\Models\Product)->getTable() . '.name',
                    DB::raw('SUM(' . (new \App\Models\OrderProduct)->getTable() . '.quantity) as total_quantity'),
                    DB::raw('SUM(' . (new \App\Models\OrderProduct)->getTable() . '.total_price) as total_revenue')
                )
                ->groupBy((new \App\Models\Product)->getTable() . '.id', (new \App\Models\Product)->getTable() . '.name')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();
                return Response::json($results->toArray());
            }

            if ($metric === 'top_returned') {
                $query->join((new \App\Models\OrderRefund)->getTable(), (new \App\Models\OrderRefund)->getTable() . '.product_id', '=', (new \App\Models\OrderProduct)->getTable() . '.product_id')
                    ->whereColumn((new \App\Models\OrderRefund)->getTable() . '.order_id', (new \App\Models\Order)->getTable() . '.id');

                $results = $query->select(
                    (new \App\Models\Product)->getTable() . '.id',
                    (new \App\Models\Product)->getTable() . '.name',
                    DB::raw('SUM(' . (new \App\Models\OrderRefund)->getTable() . '.quantity) as total_returned')
                )
                ->groupBy((new \App\Models\Product)->getTable() . '.id', (new \App\Models\Product)->getTable() . '.name')
                ->orderBy('total_returned', 'desc')
                ->limit($limit)
                ->get();
                return Response::json($results->toArray());
            }

            // Default: 'volume'
            $results = $query->select(
                DB::raw('SUM(' . (new \App\Models\OrderProduct)->getTable() . '.quantity) as total_items_sold'),
                DB::raw('SUM(' . (new \App\Models\OrderProduct)->getTable() . '.total_price) as gross_revenue')
            )->first();

            return Response::json($results ? $results->toArray() : []);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
