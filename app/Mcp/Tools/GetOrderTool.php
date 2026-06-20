<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\OrdersService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetOrderTool extends Tool
{
    protected string $name = 'get_order';

    protected string $description = 'Retrieve a single order with all details including products, payments, addresses, taxes, and customer information. Lookup by numeric ID or order code.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()
                ->description('The order ID or order code to look up.')
                ->required(),
            'pivot' => $schema->string()
                ->description('Whether the identifier is an "id" (numeric) or "code" (human-readable order code). Defaults to "id".')
                ->enum(['id', 'code'])
                ->default('id'),
        ];
    }

    public function handle(Request $request, OrdersService $service): Response
    {
        try {
            $order = $service->getOrder(
                $request->get('identifier'),
                $request->get('pivot', 'id')
            );

            return Response::json($order->toArray());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
