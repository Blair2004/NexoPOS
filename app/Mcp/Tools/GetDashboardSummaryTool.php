<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\ReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetDashboardSummaryTool extends Tool
{
    protected string $name = 'get_dashboard_summary';

    protected string $description = 'Retrieve the daily sales summary dashboard including total sales, orders, taxes, and other key metrics for a given date range. Defaults to today if no dates are specified.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'date_start' => $schema->string()
                ->description( 'Start date in Y-m-d or Y-m-d H:i:s format. Defaults to today.' )
                ->nullable(),
            'date_end' => $schema->string()
                ->description( 'End date in Y-m-d or Y-m-d H:i:s format. Defaults to today.' )
                ->nullable(),
        ];
    }

    public function handle( Request $request, ReportService $service ): Response
    {
        try {
            $report = $service->computeDayReport(
                $request->get( 'date_start' ),
                $request->get( 'date_end' )
            );

            return Response::json( $report->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
