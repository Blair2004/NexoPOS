<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\PaymentTypesResource;
use App\Mcp\Resources\ProductCategoriesResource;
use App\Mcp\Resources\StoreConfigResource;
use App\Mcp\Resources\TaxGroupsResource;
use App\Mcp\Tools\GetCustomerTool;
use App\Mcp\Tools\GetDashboardSummaryTool;
use App\Mcp\Tools\GetLowStockProductsTool;
use App\Mcp\Tools\GetOrderTool;
use App\Mcp\Tools\GetProductTool;
use App\Mcp\Tools\SearchCustomersTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\SearchOrdersTool;
use App\Mcp\Tools\SearchProductSalesTool;
use App\Mcp\Tools\SearchWalletHistoryTool;
use App\Mcp\Tools\CreateCategoryTool;
use App\Mcp\Tools\UpdateCategoryTool;
use App\Mcp\Tools\UpdateProductTool;    
use App\Mcp\Tools\UpdateSettingsTool;
use App\Mcp\Tools\UploadMediaTool;
use App\Mcp\Tools\DeleteMediaTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('NexoPOS MCP Server')]
#[Version('1.0.0')]
#[Instructions(
    'This server exposes read and write tools and resources for the NexoPOS point-of-sale system. ' .
    'Use search_products or get_product to look up product catalog items. ' .
    'Use search_customers or get_customer to find customer records. ' .
    'Use search_orders to query and filter multiple orders, or get_order to retrieve a specific order. ' .
    'Use search_product_sales to aggregate product metrics such as sales volume, most purchased, or top returned. ' .
    'Use search_wallet_history to track customer account operations (ADD, DEDUCT, PAYMENT, REFUND). ' .
    'Use get_low_stock_products to identify inventory that needs restocking. ' .
    'Use get_dashboard_summary to retrieve sales metrics for a given day or date range. ' .
    'Use create_category, update_category, and delete_category to manage product categories. ' .
    'Use create_product, update_product, and delete_product to manage products. ' .
    'Use update_settings to modify global store settings and options. ' .
    'Use upload_media and delete_media to manage files and images in the media library. ' .
    'Resources provide reference data: store-config for settings, payment-types for accepted payment methods, ' .
    'product-categories for the category tree, and tax-groups for all configured tax rates.'

)]
class POSServer extends Server
{
    protected array $tools = [
        SearchProductsTool::class,
        GetProductTool::class,
        GetLowStockProductsTool::class,
        SearchCustomersTool::class,
        GetCustomerTool::class,
        GetOrderTool::class,
        GetDashboardSummaryTool::class,
        SearchOrdersTool::class,
        SearchProductSalesTool::class,
        SearchWalletHistoryTool::class,
        CreateCategoryTool::class,
        UpdateCategoryTool::class,
        UpdateProductTool::class,
        UpdateSettingsTool::class,
        UploadMediaTool::class,
        DeleteMediaTool::class,
    ];

    protected array $resources = [
        StoreConfigResource::class,
        PaymentTypesResource::class,
        ProductCategoriesResource::class,
        TaxGroupsResource::class,
    ];

    protected array $prompts = [
        //
    ];
}
