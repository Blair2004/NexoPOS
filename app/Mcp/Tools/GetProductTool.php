<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetProductTool extends Tool
{
    protected string $name = 'get_product';

    protected string $description = 'Retrieve a single product by its ID, barcode, or SKU. Exactly one of id, barcode, or sku must be provided.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The product\'s numeric ID.')
                ->nullable(),
            'barcode' => $schema->string()
                ->description('The product\'s barcode string.')
                ->nullable(),
            'sku' => $schema->string()
                ->description('The product\'s SKU string.')
                ->nullable(),
        ];
    }

    public function handle(Request $request, ProductService $service): Response
    {
        try {
            $id      = $request->get('id');
            $barcode = $request->get('barcode');
            $sku     = $request->get('sku');

            if ($id !== null) {
                $product = $service->get((int) $id);
            } elseif ($barcode !== null) {
                $product = $service->getProductUsingBarcode($barcode);
                if ($product === false) {
                    return Response::error("No product found with barcode: {$barcode}");
                }
            } elseif ($sku !== null) {
                $product = $service->getProductUsingSKU($sku);
                if ($product === false) {
                    return Response::error("No product found with SKU: {$sku}");
                }
            } else {
                return Response::error('Provide at least one of: id, barcode, or sku.');
            }

            return Response::json($product->toArray());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
