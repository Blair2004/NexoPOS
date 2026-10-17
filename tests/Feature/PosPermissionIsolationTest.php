<?php

namespace Tests\Feature;

use App\Http\Resources\PosProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PosPermissionIsolationTest extends TestCase
{
    #[DataProvider( 'orderAccessMatrix' )]
    public function test_order_access_permission_precedence( array $permissions, int $authorId, bool $expected ): void
    {
        $user = Mockery::mock( User::class )->makePartial();
        $user->id = 10;
        Gate::shouldReceive( 'forUser' )->with( $user )->andReturnSelf();
        Gate::shouldReceive( 'any' )->andReturnUsing(
            fn( array $requested ): bool => count( array_intersect( $requested, $permissions ) ) > 0
        );
        Gate::shouldReceive( 'allows' )->andReturnUsing(
            fn( string $requested ): bool => in_array( $requested, $permissions, true )
        );

        $order = new Order;
        $order->id = 99;
        $order->author_id = $authorId;

        $this->assertSame( $expected, app( OrderAccessService::class )->canAccess( $user, $order ) );
    }

    public static function orderAccessMatrix(): array
    {
        return [
            'generic can read foreign' => [[OrderAccessService::READ_ALL], 20, true],
            'pos all can read foreign' => [[OrderAccessService::POS_READ_ALL], 20, true],
            'pos own can read own' => [[OrderAccessService::POS_READ_OWN], 10, true],
            'pos own cannot read foreign' => [[OrderAccessService::POS_READ_OWN], 20, false],
            'no read permission is denied' => [[], 10, false],
        ];
    }

    public function test_pos_product_resource_hides_cost_fields_without_permission(): void
    {
        $user = Mockery::mock( User::class )->makePartial();
        Gate::shouldReceive( 'forUser' )->with( $user )->andReturnSelf();
        Gate::shouldReceive( 'allows' )->with( 'nexopos.pos.edit-purchase-price' )->andReturnFalse();
        $request = Request::create( '/api/pos/catalog/products/search' );
        $request->setUserResolver( fn(): User => $user );

        $product = new Product;
        $product->forceFill( [
            'id' => 1,
            'name' => 'Sale product',
            'purchase_price' => 100,
            'unit_quantities' => [[
                'id' => 2,
                'visible' => true,
                'sale_price' => 150,
                'purchase_price' => 100,
                'cogs' => 100,
            ]],
        ] );

        $resource = PosProductResource::make( $product )->resolve( $request );

        $this->assertArrayNotHasKey( 'purchase_price', $resource );
        $this->assertArrayNotHasKey( 'purchase_price', $resource['unit_quantities'][0] );
        $this->assertArrayNotHasKey( 'cogs', $resource['unit_quantities'][0] );
        $this->assertSame( 150, $resource['unit_quantities'][0]['sale_price'] );
    }

    public function test_order_access_scope_supports_eloquent_and_query_builders(): void
    {
        $user = new User;
        $user->id = 10;
        Gate::shouldReceive( 'forUser' )->with( $user )->andReturnSelf();
        Gate::shouldReceive( 'any' )->twice()->andReturnFalse();
        Gate::shouldReceive( 'allows' )->twice()->with( OrderAccessService::POS_READ_OWN )->andReturnTrue();

        $eloquentQuery = Order::query();
        $queryBuilder = DB::table( ( new Order )->getTable() );
        $service = app( OrderAccessService::class );

        $this->assertSame( $eloquentQuery, $service->scopeAccessible( $eloquentQuery, $user ) );
        $this->assertSame( $queryBuilder, $service->scopeAccessible( $queryBuilder, $user ) );
        $this->assertStringContainsString( 'author_id', $eloquentQuery->toSql() );
        $this->assertStringContainsString( 'author_id', $queryBuilder->toSql() );
        $this->assertSame( [10], $eloquentQuery->getBindings() );
        $this->assertSame( [10], $queryBuilder->getBindings() );
    }

    public function test_dedicated_pos_routes_are_registered(): void
    {
        $routes = collect( Route::getRoutes()->getRoutes() )->map( fn( $route ) => $route->uri() );

        $this->assertContains( 'api/pos/catalog/products/search', $routes );
        $this->assertContains( 'api/pos/orders/{order}', $routes );
        $this->assertContains( 'api/pos/orders/{order}/products', $routes );
    }
}
