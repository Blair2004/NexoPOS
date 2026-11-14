<?php

namespace Tests\Feature;

use App\Crud\ProductCrud;
use App\Models\Product;
use App\Models\Role;
use App\Models\UnitGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_product_with_a_default_purchase_unit_and_no_scheduled_reorder_quantity(): void
    {
        $this->withoutExceptionHandling();

        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $product = Product::firstOrFail();
        $unit = UnitGroup::findOrFail( $product->unit_group )->units()->firstOrFail();
        $product->scheduled_reorder_quantity = 0;
        $form = app( ProductCrud::class )->getForm( $product );
        $reorderField = collect( data_get( $form, 'variations.0.tabs.units.fields' ) )
            ->firstWhere( 'name', 'scheduled_reorder_quantity' );
        $this->assertSame( '', $reorderField[ 'value' ] );

        $product->scheduled_reorder_quantity = 5;
        $product->save();

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', '/api/products/' . $product->id, [
                'name' => 'Sample Product',
                'variations' => [
                    [
                        '$primary' => true,
                        'expiracy' => [
                            'expires' => 0,
                            'on_expiration' => 'prevent_sales',
                        ],
                        'identification' => [
                            'barcode' => $product->barcode,
                            'barcode_type' => $product->barcode_type,
                            'category_id' => $product->category_id,
                            'description' => __( 'Created via tests' ),
                            'product_type' => 'product',
                            'type' => Product::TYPE_MATERIALIZED,
                            'sku' => 'sample-sku',
                            'status' => 'available',
                            'stock_management' => 'enabled',
                        ],
                        'images' => [],
                        'groups' => [],
                        'taxes' => [
                            'tax_group_id' => 1,
                            'tax_type' => 'exclusive',
                        ],
                        'units' => [
                            'selling_group' => [
                                [
                                    'sale_price_edit' => 10,
                                    'wholesale_price_edit' => 9.55,
                                    'unit_id' => $unit->id,
                                ],
                            ],
                            'unit_group' => $product->unit_group,
                            'default_purchase_unit_id' => $unit->id,
                            'scheduled_reorder_quantity' => '',
                        ],
                    ],
                ],
            ] );

        $response->assertStatus( 200 );
        $product->refresh();
        $this->assertEquals( $unit->id, $product->default_purchase_unit_id );
        $this->assertNull( $product->scheduled_reorder_quantity );
    }
}
