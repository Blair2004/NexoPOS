<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Modules\NsOxen\Services\AuthorizeOxenOperation;
use Modules\NsOxen\Services\CatalogCreationService;
use Modules\NsOxen\Services\ConfigurationManagementService;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ProductManagementService;
use Modules\NsOxen\Services\SafeWriteTools;
use Modules\NsOxen\Services\ToolRegistry;
use Tests\TestCase;

class OxenConfigurationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_tool_contracts_are_strict_and_use_existing_permissions(): void
    {
        $registry = app( ToolRegistry::class );
        $expectations = [
            'create_unit_group' => ['nexopos.create.products-units', false],
            'update_unit' => ['nexopos.update.products-units', false],
            'create_tax' => ['nexopos.create.taxes', false],
            'update_coupon' => ['nexopos.update.coupons', false],
            'create_customer_group' => ['nexopos.create.customers-groups', false],
            'update_media' => ['nexopos.update.medias', true],
            'generate_store_logo' => [['nexopos.upload.medias', 'manage.options'], true],
            'search_tax_groups' => ['nexopos.read.taxes', false],
            'get_coupon' => ['nexopos.read.coupons', false],
        ];

        foreach ( $expectations as $name => [$permission, $confirmation] ) {
            $definition = $registry->definition( $name );
            $this->assertNotNull( $definition );
            $this->assertFalse( $definition->inputSchema['additionalProperties'] );
            $this->assertSame( (array) $permission, $definition->permissions );
            $this->assertSame( $confirmation, $definition->requiresConfirmation );
        }

        $coupon = $registry->definition( 'create_coupon' );
        $this->assertTrue( Validator::make( ['name' => 'Invalid', 'code' => 'DUP', 'type' => 'flat_discount', 'discount_value' => 1, 'product_ids' => [1, 1], 'idempotency_key' => 'duplicate'], $coupon->rules )->fails() );
        $this->assertSame( 100, $coupon->inputSchema['properties']['product_ids']['maxItems'] );
    }

    public function test_registry_resolves_configuration_service_for_configuration_tools(): void
    {
        $migration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $migration->up();

        $user = User::query()->firstOrFail();
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

        $result = $registry->execute( $user, 'search_tax_groups', [
            'search' => 'missing-oxen-tax-group',
            'limit' => 1,
        ] );

        $this->assertTrue( $result['ok'] );
        $this->assertSame( [], $result['data'] );
    }

    public function test_units_preserve_patch_fields_and_base_unit_exclusivity(): void
    {
        $service = app( ConfigurationManagementService::class );
        $user = User::query()->firstOrFail();
        $otherUser = User::query()->whereKeyNot( $user->id )->first() ?? $user;
        $group = $service->saveUnitGroup( $user, null, ['name' => 'Oxen quantities', 'description' => 'Test group'] );
        $first = $service->saveUnit( $user, null, ['name' => 'Each', 'group_id' => $group['id'], 'value' => 1, 'base_unit' => true] );
        $second = $service->saveUnit( $user, null, ['name' => 'Case', 'identifier' => 'oxen-case', 'group_id' => $group['id'], 'value' => 12, 'base_unit' => true] );

        $this->assertFalse( (bool) Unit::query()->findOrFail( $first['id'] )->base_unit );
        $this->assertTrue( (bool) Unit::query()->findOrFail( $second['id'] )->base_unit );

        $updated = $service->saveUnit( $otherUser, $second['id'], ['description' => 'A dozen'] );
        $this->assertSame( 'oxen-case', $updated['identifier'] );
        $this->assertSame( 12.0, (float) $updated['value'] );
        $this->assertSame( $otherUser->id, $updated['author_id'] );
    }

    public function test_tax_group_search_includes_tax_count_and_total_rate(): void
    {
        $service = app( ConfigurationManagementService::class );
        $user = User::query()->firstOrFail();
        $group = $service->saveTaxGroup( $user, null, ['name' => 'Oxen VAT', 'description' => null] );
        $service->saveTax( $user, null, ['name' => 'State', 'rate' => 7.5, 'tax_group_id' => $group['id'] ] );
        $service->saveTax( $user, null, ['name' => 'City', 'rate' => 2.5, 'tax_group_id' => $group['id'] ] );

        $result = collect( $service->searchTaxGroups( 'Oxen VAT', 10 ) )->firstWhere( 'id', $group['id'] );

        $this->assertNotNull( $result );
        $this->assertSame( 2, (int) $result->taxes_count );
        $this->assertSame( 10.0, (float) $result->total_rate );
        $this->assertSame( $user->id, Tax::query()->where( 'tax_group_id', $group['id'] )->firstOrFail()->author_id );
    }

    public function test_coupon_eligibility_updates_distinguish_omitted_from_empty(): void
    {
        $service = app( ConfigurationManagementService::class );
        $user = User::query()->firstOrFail();
        $product = Product::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $customer = Customer::query()->firstOrFail();
        $customerGroup = CustomerGroup::query()->firstOrFail();
        $created = $service->saveCoupon( $user, null, [
            'name' => 'Oxen coupon', 'code' => 'OXEN-' . uniqid(), 'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10, 'minimum_cart_value' => 5, 'maximum_cart_value' => 0,
            'product_ids' => [$product->id], 'category_ids' => [$category->id],
            'customer_ids' => [$customer->id], 'customer_group_ids' => [$customerGroup->id],
        ] );

        $patched = $service->saveCoupon( $user, $created['id'], ['discount_value' => 15, 'product_ids' => []] );

        $this->assertSame( [], $patched['product_ids'] );
        $this->assertSame( [$category->id], $patched['category_ids'] );
        $this->assertSame( [$customer->id], $patched['customer_ids'] );
        $this->assertSame( [$customerGroup->id], $patched['customer_group_ids'] );
        $this->assertSame( 15.0, (float) $patched['discount_value'] );
    }

    public function test_updates_reject_empty_patches_and_invalid_relationships(): void
    {
        $service = app( ConfigurationManagementService::class );
        $user = User::query()->firstOrFail();
        $group = UnitGroup::query()->firstOrFail();

        try {
            $service->saveUnitGroup( $user, $group->id, [] );
            $this->fail( 'An empty patch unexpectedly succeeded.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'VALIDATION_FAILED', $exception->errorCode );
        }

        $this->expectException( OxenException::class );
        $service->saveTax( $user, null, ['name' => 'Invalid', 'rate' => 1, 'tax_group_id' => PHP_INT_MAX] );
    }
}
