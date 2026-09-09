<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Provider;
use App\Models\ScaleRange;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery;
use App\Services\ReportService;
use Modules\NsOxen\Services\AuthorizeOxenOperation;
use Modules\NsOxen\Services\CatalogCreationService;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\OxenInputValidator;
use Modules\NsOxen\Services\ProductManagementService;
use Modules\NsOxen\Services\SafeWriteTools;
use Modules\NsOxen\Services\ToolRegistry;
use Tests\TestCase;

class OxenCatalogCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs( User::query()->firstOrFail() );
    }

    public function test_creation_and_import_schemas_are_bounded_and_safe(): void
    {
        $registry = app( ToolRegistry::class );

        $this->assertSame( 50, data_get( $registry->definition( 'import_products' )?->inputSchema, 'properties.products.maxItems' ) );
        $this->assertFalse( data_get( $registry->definition( 'create_product' )?->inputSchema, 'additionalProperties' ) );
        $this->assertFalse( data_get( $registry->definition( 'create_customer' )?->inputSchema, 'properties.address.additionalProperties' ) );
        $this->assertArrayNotHasKey( 'password', data_get( $registry->definition( 'create_customer' )?->inputSchema, 'properties' ) );
        $this->assertArrayNotHasKey( 'quantity', data_get( $registry->definition( 'create_product' )?->inputSchema, 'properties.units.items.properties' ) );
    }

    public function test_create_product_category_records_the_executing_user_as_author(): void
    {
        $migration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $migration->up();

        $user = User::query()->firstOrFail();
        $name = 'Oxen category ' . Str::random( 8 );
        $authorization = Mockery::mock( AuthorizeOxenOperation::class );
        $authorization->shouldReceive( 'ensure' )->once();
        $registry = new ToolRegistry(
            $authorization,
            app( SafeWriteTools::class ),
            app( ProductManagementService::class ),
            app( CatalogCreationService::class ),
            app( ReportService::class ),
            app(),
        );

        $result = $registry->execute( $user, 'create_product_category', [
            'name' => $name,
            'description' => 'Created through an approved Oxen action.',
            'idempotency_key' => (string) Str::uuid(),
        ] );

        $category = ProductCategory::query()->findOrFail( $result['data']['id'] );

        $this->assertModelExists( $category );
        $this->assertSame( $user->id, $category->author_id );
        $this->assertSame( $name, $category->name );
    }

    public function test_nested_assistant_supplied_fields_are_rejected(): void
    {
        $definition = app( ToolRegistry::class )->definition( 'create_product' );

        $this->expectException( OxenException::class );
        OxenInputValidator::ensureShape( [
            'name' => 'Unsafe product',
            'category_id' => 1,
            'unit_group_id' => 1,
            'units' => [['unit_id' => 1, 'sale_price' => 10, 'quantity' => 500]],
        ], $definition->inputSchema );
    }

    public function test_report_contract_accepts_kpi_sections_and_creates_pdf_and_preview(): void
    {
        Storage::fake( 'ns-temp' );
        $definition = app( ToolRegistry::class )->definition( 'generate_report' );
        $input = [
            'title' => 'Yesterday sales',
            'sections' => [[
                'type' => 'kpi_grid',
                'title' => 'Summary',
                'items' => [
                    ['label' => 'Orders', 'value' => 12],
                    ['label' => 'Sales', 'value' => 240.5],
                ],
            ]],
            'idempotency_key' => 'report-regression',
        ];

        $this->assertFalse( Validator::make( $input, $definition->rules )->fails() );
        $result = app( SafeWriteTools::class )->report( $input );

        Storage::disk( 'ns-temp' )->assertExists( 'mcp-reports/' . $result['filename'] );
        Storage::disk( 'ns-temp' )->assertExists( 'mcp-reports/' . $result['html_filename'] );
        $this->assertSame( 'pdf', $result['format'] );
        $this->assertStringContainsString( 'mcp/reports', $result['download_url'] );
    }

    public function test_report_can_create_a_formula_safe_csv_export(): void
    {
        Storage::fake( 'ns-temp' );
        $input = [
            'title' => 'Product export',
            'format' => 'csv',
            'sections' => [[
                'type' => 'table',
                'columns' => ['Product', 'Sales'],
                'rows' => [['Product' => '=HYPERLINK("https://example.com")', 'Sales' => 42]],
            ]],
            'idempotency_key' => 'csv-report-regression',
        ];

        $definition = app( ToolRegistry::class )->definition( 'generate_report' );
        $this->assertFalse( Validator::make( $input, $definition->rules )->fails() );

        $result = app( SafeWriteTools::class )->report( $input );
        $contents = Storage::disk( 'ns-temp' )->get( 'mcp-reports/' . $result['filename'] );

        $this->assertSame( 'csv', $result['format'] );
        $this->assertStringContainsString( "'=HYPERLINK", $contents );
        Storage::disk( 'ns-temp' )->assertExists( 'mcp-reports/' . $result['filename'] );
    }

    public function test_create_product_uses_product_service_contract_without_initial_stock(): void
    {
        ns()->option->set( 'ns_pos_max_pinned_products', 100 );
        $category = ProductCategory::factory()->create();
        $unit = Unit::query()->firstOrFail();

        $result = app( CatalogCreationService::class )->createProduct( [
            'name' => 'Oxen ' . Str::random( 8 ),
            'category_id' => $category->id,
            'unit_group_id' => $unit->group_id,
            'type' => Product::TYPE_DEMATERIALIZED,
            'units' => [[
                'unit_id' => $unit->id,
                'sale_price' => 8.30,
                'wholesale_price' => 6.10,
                'visible' => true,
            ]],
        ] );

        $product = Product::query()->findOrFail( $result['product_id'] );
        $this->assertSame( Product::TYPE_DEMATERIALIZED, $product->type );
        $this->assertSame( 0.0, (float) $product->unit_quantities()->firstOrFail()->quantity );
        $this->assertSame( 8.30, (float) $product->unit_quantities()->firstOrFail()->sale_price_edit );
    }

    public function test_create_weighable_product_generates_exactly_one_plu(): void
    {
        ns()->option->set( 'ns_scale_barcode_enabled', 'yes' );
        $scaleRange = ScaleRange::query()->firstOrFail();
        $scaleRange->update( ['next_scale_plu' => $scaleRange->range_start] );
        ns()->option->set( 'ns_scale_barcode_product_length', strlen( (string) $scaleRange->range_start ) );
        $category = ProductCategory::factory()->create( ['scale_range_id' => $scaleRange->id] );
        $unit = Unit::query()->firstOrFail();

        $result = app( CatalogCreationService::class )->createProduct( [
            'name' => 'Weighted ' . Str::random( 8 ),
            'category_id' => $category->id,
            'unit_group_id' => $unit->group_id,
            'units' => [['unit_id' => $unit->id, 'sale_price' => 12, 'is_weighable' => true]],
        ] );

        $quantity = Product::query()->findOrFail( $result['product_id'] )->unit_quantities()->firstOrFail();
        $this->assertSame( (string) $scaleRange->range_start, $quantity->scale_plu );
        $this->assertSame( $scaleRange->range_start + 1, $scaleRange->fresh()->next_scale_plu );
    }

    public function test_provider_and_customer_creation_allowlist_fields_and_preserve_services(): void
    {
        $group = CustomerGroup::query()->firstOrFail();
        $suffix = Str::lower( Str::random( 10 ) );
        $service = app( CatalogCreationService::class );

        $providerResult = $service->createProvider( [
            'first_name' => 'Provider',
            'email' => "provider-{$suffix}@example.com",
            'amount_due' => 999,
        ] );
        $customerResult = $service->createCustomer( [
            'first_name' => 'Customer',
            'email' => "customer-{$suffix}@example.com",
            'group_id' => $group->id,
            'address' => ['billing' => ['city' => 'Accra', 'customer_id' => 999]],
        ] );

        $provider = Provider::query()->findOrFail( $providerResult['id'] );
        $customer = Customer::query()->findOrFail( $customerResult['id'] );
        $this->assertNotSame( 999.0, (float) $provider->amount_due );
        $this->assertSame( $group->id, $customer->group_id );
        $this->assertSame( 'Accra', $customer->billing->city );
        $this->assertSame( $customer->id, $customer->billing->customer_id );
    }

    public function test_import_participates_in_the_action_transaction_and_rolls_back_every_created_record(): void
    {
        ns()->option->set( 'ns_pos_max_pinned_products', 100 );
        $unit = Unit::query()->firstOrFail();
        $categoryName = 'Imported ' . Str::random( 8 );
        $firstName = 'First ' . Str::random( 8 );
        $common = [
            'category' => ['name' => $categoryName, 'create_if_missing' => true],
            'unit_group' => ['id' => $unit->group_id],
            'units' => [['unit_id' => $unit->id, 'sale_price' => 10]],
        ];

        try {
            DB::transaction( function () use ( $firstName, $common ): void {
                app( CatalogCreationService::class )->importProducts( [
                    'products' => [['name' => $firstName, ...$common]],
                ] );
                throw new \RuntimeException( 'Force action rollback.' );
            } );
            $this->fail( 'The forced action rollback unexpectedly committed.' );
        } catch ( \RuntimeException $exception ) {
            $this->assertSame( 'Force action rollback.', $exception->getMessage() );
        }

        $this->assertFalse( ProductCategory::query()->where( 'name', $categoryName )->exists() );
        $this->assertFalse( Product::query()->where( 'name', $firstName )->exists() );
    }

    public function test_product_sales_performance_includes_products_with_zero_sales(): void
    {
        $product = Product::factory()->create( ['pinned' => false] );

        $result = app( ToolRegistry::class )->getProductSalesPerformance( [
            'date_start' => now()->startOfMonth()->toDateString(),
            'date_end' => now()->endOfMonth()->toDateString(),
            'include_zero_sales' => true,
            'max_quantity' => 0,
        ], 50 );

        $row = collect( $result['products'] )->firstWhere( 'product_id', $product->id );
        $this->assertNotNull( $row );
        $this->assertSame( 0.0, (float) $row->quantity_sold );
        $this->assertArrayHasKey( 'remaining', $result['pin_capacity'] );
    }

}
