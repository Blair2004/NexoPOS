<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGallery;
use App\Models\ProductUnitQuantity;
use App\Models\ScaleRange;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ProductManagementService;
use Modules\NsOxen\Services\SafeWriteTools;
use Modules\NsOxen\Services\ToolRegistry;
use Tests\TestCase;

class OxenProductManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_management_tool_schemas_are_bounded_and_reject_unknown_fields(): void
    {
        $registry = app( ToolRegistry::class );
        $products = $registry->definition( 'update_products' );
        $units = $registry->definition( 'update_product_unit_quantities' );
        $image = $registry->definition( 'generate_product_image' );

        $this->assertSame( 50, data_get( $products?->inputSchema, 'properties.product_ids.maxItems' ) );
        $this->assertFalse( data_get( $products?->inputSchema, 'properties.changes.additionalProperties' ) );
        $this->assertSame( 50, data_get( $units?->inputSchema, 'properties.unit_quantity_ids.maxItems' ) );
        $this->assertTrue( $image?->requiresConfirmation );
        $this->assertSame( ['nexopos.update.products', 'nexopos.upload.medias'], $image?->permissions );
        $this->assertArrayNotHasKey( 'discount', data_get( $products?->inputSchema, 'properties.changes.properties' ) );

        $unsupported = Validator::make( ['product_ids' => [1], 'changes' => ['discount' => 10], 'idempotency_key' => 'key'], $products->rules );
        $oversized = Validator::make( ['product_ids' => range( 1, 51 ), 'changes' => ['status' => 'available'], 'idempotency_key' => 'key'], $products->rules );
        $this->assertTrue( $unsupported->fails() );
        $this->assertTrue( $oversized->fails() );
    }

    public function test_products_are_updated_atomically_and_tax_prices_are_recomputed(): void
    {
        ns()->option->set( 'ns_pos_max_pinned_products', 100 );
        $category = ProductCategory::factory()->create();
        $products = Product::factory()->count( 2 )->create( ['pinned' => false, 'type' => Product::TYPE_DEMATERIALIZED] );
        $unit = Unit::query()->firstOrFail();
        foreach ( $products as $product ) {
            $this->createUnitQuantity( [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'sale_price_edit' => 100,
                'wholesale_price_edit' => 50,
            ] );
        }
        $taxGroup = TaxGroup::query()->firstOrFail();

        $result = app( ProductManagementService::class )->updateProducts( [
            'product_ids' => $products->pluck( 'id' )->all(),
            'changes' => [
                'category_id' => $category->id,
                'auto_cogs' => false,
                'tax_group_id' => $taxGroup->id,
                'tax_type' => 'exclusive',
                'expires' => true,
                'on_expiration' => Product::EXPIRES_ALLOW_SALES,
                'barcode_type' => 'code128',
                'type' => Product::TYPE_MATERIALIZED,
                'status' => Product::STATUS_UNAVAILABLE,
                'stock_management' => Product::STOCK_MANAGEMENT_DISABLED,
                'pinned' => true,
            ],
        ] );

        $this->assertSame( 2, $result['updated_count'] );
        foreach ( $products as $product ) {
            $fresh = $product->fresh();
            $this->assertSame( $category->id, $fresh->category_id );
            $this->assertFalse( $fresh->auto_cogs );
            $this->assertSame( $taxGroup->id, $fresh->tax_group_id );
            $this->assertSame( 'exclusive', $fresh->tax_type );
            $this->assertTrue( (bool) $fresh->expires );
            $this->assertSame( Product::EXPIRES_ALLOW_SALES, $fresh->on_expiration );
            $this->assertSame( 'code128', $fresh->barcode_type );
            $this->assertSame( Product::TYPE_MATERIALIZED, $fresh->type );
            $this->assertSame( Product::STATUS_UNAVAILABLE, $fresh->status );
            $this->assertSame( Product::STOCK_MANAGEMENT_DISABLED, $fresh->stock_management );
            $this->assertTrue( (bool) $fresh->pinned );
            $this->assertGreaterThanOrEqual( 100, $fresh->unit_quantities()->firstOrFail()->sale_price_gross );
        }
    }

    public function test_missing_batch_target_rolls_back_the_complete_product_update(): void
    {
        $product = Product::factory()->create( ['status' => Product::STATUS_AVAILABLE] );

        try {
            app( ProductManagementService::class )->updateProducts( [
                'product_ids' => [$product->id, PHP_INT_MAX],
                'changes' => ['status' => Product::STATUS_UNAVAILABLE],
            ] );
            $this->fail( 'A partial product batch unexpectedly succeeded.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'NOT_FOUND', $exception->errorCode );
        }

        $this->assertSame( Product::STATUS_AVAILABLE, $product->fresh()->status );
    }

    public function test_grouped_products_cannot_transition(): void
    {
        $grouped = Product::factory()->create( ['type' => Product::TYPE_GROUPED] );
        $this->expectException( OxenException::class );
        app( ProductManagementService::class )->updateProducts( [
            'product_ids' => [$grouped->id],
            'changes' => ['type' => Product::TYPE_MATERIALIZED],
        ] );
    }

    public function test_pinned_limit_rejects_the_complete_batch(): void
    {
        $products = Product::factory()->count( 2 )->create( ['pinned' => false] );
        $currentlyPinned = Product::query()->where( 'pinned', true )->count();
        ns()->option->set( 'ns_pos_max_pinned_products', $currentlyPinned + 1 );

        try {
            app( ProductManagementService::class )->updateProducts( [
                'product_ids' => $products->pluck( 'id' )->all(),
                'changes' => ['pinned' => true],
            ] );
            $this->fail( 'A product batch exceeded the pinned limit.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'VALIDATION_FAILED', $exception->errorCode );
        }

        $this->assertSame( 0, Product::query()->whereIn( 'id', $products->pluck( 'id' ) )->where( 'pinned', true )->count() );
    }

    public function test_unit_settings_update_and_disabling_weighing_preserves_the_plu(): void
    {
        $product = Product::factory()->create();
        $unitQuantity = $this->createUnitQuantity( [
            'product_id' => $product->id,
            'unit_id' => Unit::query()->firstOrFail()->id,
            'is_weighable' => true,
            'scale_plu' => '01234',
            'visible' => true,
            'stock_alert_enabled' => false,
        ] );

        app( ProductManagementService::class )->updateProductUnitQuantities( [
            'unit_quantity_ids' => [$unitQuantity->id],
            'changes' => ['is_weighable' => false, 'visible' => false, 'stock_alert_enabled' => true],
        ] );

        $fresh = $unitQuantity->fresh();
        $this->assertFalse( (bool) $fresh->is_weighable );
        $this->assertFalse( (bool) $fresh->visible );
        $this->assertTrue( (bool) $fresh->stock_alert_enabled );
        $this->assertSame( '01234', $fresh->scale_plu );
    }

    public function test_weighing_requires_scale_configuration(): void
    {
        ns()->option->set( 'ns_scale_barcode_enabled', 'no' );
        $product = Product::factory()->create();
        $unitQuantity = $this->createUnitQuantity( [
            'product_id' => $product->id,
            'unit_id' => Unit::query()->firstOrFail()->id,
            'is_weighable' => false,
        ] );

        $this->expectException( OxenException::class );
        app( ProductManagementService::class )->updateProductUnitQuantities( [
            'unit_quantity_ids' => [$unitQuantity->id],
            'changes' => ['is_weighable' => true],
        ] );
    }

    public function test_enabling_weighing_generates_unique_plus_for_the_complete_batch(): void
    {
        ns()->option->set( 'ns_scale_barcode_enabled', 'yes' );
        $scaleRange = ScaleRange::query()->firstOrFail();
        $scaleRange->next_scale_plu = $scaleRange->range_start;
        $scaleRange->save();
        ns()->option->set( 'ns_scale_barcode_product_length', strlen( (string) $scaleRange->range_start ) );
        $category = ProductCategory::factory()->create( ['scale_range_id' => $scaleRange->id] );
        $product = Product::factory()->create( ['category_id' => $category->id] );
        $unit = Unit::query()->firstOrFail();
        $unitQuantities = collect( [
            $this->createUnitQuantity( ['product_id' => $product->id, 'unit_id' => $unit->id] ),
            $this->createUnitQuantity( ['product_id' => $product->id, 'unit_id' => $unit->id] ),
        ] );

        app( ProductManagementService::class )->updateProductUnitQuantities( [
            'unit_quantity_ids' => $unitQuantities->pluck( 'id' )->all(),
            'changes' => ['is_weighable' => true],
        ] );

        $scalePluValues = ProductUnitQuantity::query()->whereIn( 'id', $unitQuantities->pluck( 'id' ) )->pluck( 'scale_plu' );
        $this->assertCount( 2, $scalePluValues->unique() );
        $this->assertNotContains( null, $scalePluValues );
        $this->assertSame( 2, ProductUnitQuantity::query()->whereIn( 'id', $unitQuantities->pluck( 'id' ) )->where( 'is_weighable', true )->count() );
    }

    public function test_supplied_scale_plu_must_be_in_range_and_unique(): void
    {
        ns()->option->set( 'ns_scale_barcode_enabled', 'yes' );
        $scaleRange = ScaleRange::query()->firstOrFail();
        ns()->option->set( 'ns_scale_barcode_product_length', strlen( (string) $scaleRange->range_start ) );
        $category = ProductCategory::factory()->create( ['scale_range_id' => $scaleRange->id] );
        $product = Product::factory()->create( ['category_id' => $category->id] );
        $unit = Unit::query()->firstOrFail();
        $assigned = $this->createUnitQuantity( [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'is_weighable' => true,
            'scale_plu' => (string) $scaleRange->range_start,
        ] );
        $target = $this->createUnitQuantity( ['product_id' => $product->id, 'unit_id' => $unit->id] );

        try {
            app( ProductManagementService::class )->updateProductUnitQuantities( [
                'unit_quantity_ids' => [$target->id],
                'changes' => ['is_weighable' => true, 'scale_plu' => $assigned->scale_plu],
            ] );
            $this->fail( 'A duplicate scale PLU was accepted.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'VALIDATION_FAILED', $exception->errorCode );
        }

        $this->assertFalse( (bool) $target->fresh()->is_weighable );
        $this->assertNull( $target->fresh()->scale_plu );
    }

    public function test_missing_unit_target_rolls_back_the_complete_batch(): void
    {
        $product = Product::factory()->create();
        $unitQuantity = $this->createUnitQuantity( ['product_id' => $product->id, 'unit_id' => Unit::query()->firstOrFail()->id, 'visible' => true] );

        try {
            app( ProductManagementService::class )->updateProductUnitQuantities( [
                'unit_quantity_ids' => [$unitQuantity->id, PHP_INT_MAX],
                'changes' => ['visible' => false],
            ] );
            $this->fail( 'A partial unit-quantity batch unexpectedly succeeded.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'NOT_FOUND', $exception->errorCode );
        }

        $this->assertTrue( (bool) $unitQuantity->fresh()->visible );
    }

    public function test_generated_image_becomes_primary_retains_the_old_gallery_and_is_retry_safe(): void
    {
        $this->migrateOxenTables();
        $user = User::query()->firstOrFail();
        $product = Product::factory()->create();
        Setting::query()->updateOrCreate( ['provider' => 'openai'], ['api_key' => 'sk-test', 'image_model' => 'gpt-image-2'] );
        $oldGallery = ProductGallery::query()->create( [
            'name' => 'old', 'product_id' => $product->id, 'media_id' => null, 'url' => '/old.png',
            'order' => 1, 'featured' => true, 'author_id' => $user->id,
        ] );
        $media = new Media;
        $media->name = 'generated';
        $media->extension = 'png';
        $media->slug = 'generated';
        $media->user_id = $user->id;
        $media->save();
        $media->sizes = (object) ['original' => '/storage/generated.png'];

        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'generateImage' )->once()->andReturn( $this->pngBase64() );
        $mediaService = Mockery::mock( MediaService::class );
        $mediaService->shouldReceive( 'upload' )->once()->andReturn( $media );
        $safeWrites = new SafeWriteTools( $mediaService, $provider );
        $input = ['product_id' => $product->id, 'prompt' => 'A clean retail product photo', 'idempotency_key' => 'image-provenance-1'];

        $first = $safeWrites->generateProductImage( $user, $input );
        $second = $safeWrites->generateProductImage( $user, $input );

        $this->assertSame( $first, $second );
        $this->assertFalse( (bool) $oldGallery->fresh()->featured );
        $this->assertDatabaseHas( 'nexopos_products_galleries', ['id' => $first['gallery_id'], 'featured' => true, 'uuid' => 'image-provenance-1'] );
        $this->assertSame( $media->id, $product->fresh()->thumbnail_id );
    }

    public function test_malformed_generated_image_is_rejected_before_upload(): void
    {
        $this->migrateOxenTables();
        Setting::query()->updateOrCreate( ['provider' => 'openai'], ['api_key' => 'sk-test', 'image_model' => 'gpt-image-2'] );
        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'generateImage' )->once()->andReturn( base64_encode( 'not-a-png' ) );
        $mediaService = Mockery::mock( MediaService::class );
        $mediaService->shouldNotReceive( 'upload' );
        $product = Product::factory()->create();

        $this->expectException( OxenException::class );
        ( new SafeWriteTools( $mediaService, $provider ) )->generateProductImage( User::query()->firstOrFail(), [
            'product_id' => $product->id,
            'prompt' => 'A product image',
            'idempotency_key' => 'malformed-image',
        ] );
    }

    public function test_oversized_generated_image_is_rejected_before_decoding_or_upload(): void
    {
        $this->migrateOxenTables();
        Setting::query()->updateOrCreate( ['provider' => 'openai'], ['api_key' => 'sk-test', 'image_model' => 'gpt-image-2'] );
        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'generateImage' )->once()->andReturn( str_repeat( 'A', 16 * 1024 * 1024 + 8 ) );
        $mediaService = Mockery::mock( MediaService::class );
        $mediaService->shouldNotReceive( 'upload' );
        $product = Product::factory()->create();

        $this->expectException( OxenException::class );
        ( new SafeWriteTools( $mediaService, $provider ) )->generateProductImage( User::query()->firstOrFail(), [
            'product_id' => $product->id,
            'prompt' => 'A product image',
            'idempotency_key' => 'oversized-image',
        ] );
    }

    private function pngBase64(): string
    {
        $image = imagecreatetruecolor( 1024, 1024 );
        ob_start();
        imagepng( $image );
        $contents = ob_get_clean();
        imagedestroy( $image );

        return base64_encode( $contents );
    }

    private function createUnitQuantity( array $attributes ): ProductUnitQuantity
    {
        $unitQuantity = new ProductUnitQuantity;
        $unitQuantity->product_id = $attributes['product_id'];
        $unitQuantity->unit_id = $attributes['unit_id'];
        $unitQuantity->quantity = $attributes['quantity'] ?? 10;
        $unitQuantity->sale_price = $attributes['sale_price'] ?? $attributes['sale_price_edit'] ?? 10;
        $unitQuantity->sale_price_edit = $attributes['sale_price_edit'] ?? 10;
        $unitQuantity->wholesale_price = $attributes['wholesale_price'] ?? $attributes['wholesale_price_edit'] ?? 5;
        $unitQuantity->wholesale_price_edit = $attributes['wholesale_price_edit'] ?? 5;
        $unitQuantity->is_weighable = $attributes['is_weighable'] ?? false;
        $unitQuantity->scale_plu = $attributes['scale_plu'] ?? null;
        $unitQuantity->visible = $attributes['visible'] ?? true;
        $unitQuantity->stock_alert_enabled = $attributes['stock_alert_enabled'] ?? false;
        $unitQuantity->save();

        return $unitQuantity;
    }

    private function migrateOxenTables(): void
    {
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php' )->up();
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_06_000000_add_image_model_to_oxen_settings.php' )->up();
    }
}
