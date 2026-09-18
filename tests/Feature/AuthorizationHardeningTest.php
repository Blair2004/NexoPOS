<?php

namespace Tests\Feature;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\PermissionAccess;
use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\Tax;
use App\Models\User;
use App\Services\UsersService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

final class AuthorizationHardeningTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    protected function tearDown(): void
    {
        $this->clearUsersServiceMock();
        ns()->option->set( 'ns_pos_action_permission_restricted_features', [] );

        parent::tearDown();
    }

    public function test_restrict_requires_permission_strings(): void
    {
        Gate::shouldReceive( 'allows' )
            ->once()
            ->with( 'test.permission' )
            ->andReturnTrue();

        ns()->restrict( 'test.permission' );

        $this->expectException( NotEnoughPermissionException::class );
        ns()->restrict( true );
    }

    public function test_restrict_denies_false_and_invalid_values(): void
    {
        $this->expectException( NotEnoughPermissionException::class );
        ns()->restrict( false );
    }

    public function test_restrict_denies_an_invalid_permission_value(): void
    {
        $this->expectException( NotEnoughPermissionException::class );
        ns()->restrict( [ true ] );
    }

    public function test_unprivileged_users_cannot_manage_tax_or_hold_order_crud(): void
    {
        $user = $this->createUserRoleUser();
        Sanctum::actingAs( $user, [ '*' ] );

        foreach ( [ 'ns.taxes', 'ns.hold-orders' ] as $identifier ) {
            $payload = $identifier === 'ns.taxes'
                ? [
                    'name' => 'Unauthorized Tax',
                    'general' => [ 'tax_group_id' => 1, 'rate' => 10 ],
                ]
                : [
                    'payment_status' => 'hold',
                ];

            $this->json( 'GET', "api/crud/{$identifier}" )->assertForbidden();
            $this->json( 'GET', "api/crud/{$identifier}/999999" )->assertForbidden();
            $this->json( 'POST', "api/crud/{$identifier}", $payload )->assertForbidden();
            $this->json( 'PUT', "api/crud/{$identifier}/999999", $payload )->assertForbidden();
            $this->json( 'DELETE', "api/crud/{$identifier}/999999" )->assertForbidden();
        }

        $user->delete();
    }

    public function test_authorized_users_can_read_a_single_tax_record(): void
    {
        $tax = Tax::first();
        $this->assertNotNull( $tax );

        $this->attemptAuthenticate();

        $this->json( 'GET', "api/crud/ns.taxes/{$tax->id}" )->assertOk();
    }

    public function test_temporary_pos_action_approval_is_honored(): void
    {
        $user = $this->createUserRoleUser();
        Sanctum::actingAs( $user, [ '*' ] );

        $previousFeatures = ns()->option->get( 'ns_pos_action_permission_restricted_features', [] );
        ns()->option->set( 'ns_pos_action_permission_restricted_features', [ 'nexopos.cart.product-price' ] );

        $access = new PermissionAccess;
        $access->requester_id = $user->id;
        $access->granter_id = 1;
        $access->status = PermissionAccess::GRANTED;
        $access->permission = 'nexopos.cart.product-price';
        $access->expired_at = now()->addMinute();
        $access->save();

        $this->assertTrue( app( UsersService::class )->isPosActionAllowed( 'nexopos.cart.product-price' ) );

        ns()->option->set( 'ns_pos_action_permission_restricted_features', $previousFeatures );
        $user->delete();
    }

    public function test_custom_product_price_is_rejected_on_create_and_update(): void
    {
        $this->attemptAuthenticate();
        $product = Product::notGrouped()->with( 'unit_quantities' )->first();
        $this->assertNotNull( $product );
        $unitQuantity = $product->unit_quantities->first();
        $this->assertInstanceOf( ProductUnitQuantity::class, $unitQuantity );

        $payload = $this->orderPayload( $product, $unitQuantity, [
            'unit_price' => (float) $unitQuantity->sale_price + 1,
        ] );

        $this->mockActionPermission( false );
        $this->json( 'POST', 'api/orders', $payload )->assertForbidden();

        $this->clearUsersServiceMock();
        $created = $this->json( 'POST', 'api/orders', $this->orderPayload( $product, $unitQuantity ) );
        $created->assertOk();

        $this->mockActionPermission( false );
        $payload['id'] = $created->json( 'data.order.id' );
        $this->json( 'PUT', "api/orders/{$payload['id']}", $payload )->assertForbidden();
    }

    public function test_product_and_cart_discounts_are_rejected(): void
    {
        $this->attemptAuthenticate();
        $product = Product::notGrouped()->with( 'unit_quantities' )->first();
        $this->assertNotNull( $product );
        $unitQuantity = $product->unit_quantities->first();
        $this->assertInstanceOf( ProductUnitQuantity::class, $unitQuantity );

        $productDiscount = $this->orderPayload( $product, $unitQuantity, [
            'discount' => 1,
            'discount_percentage' => 1,
            'discount_type' => 'percentage',
        ] );
        $cartDiscount = $this->orderPayload( $product, $unitQuantity, [
        ] );
        $cartDiscount['discount'] = 1;
        $cartDiscount['discount_percentage'] = 1;
        $cartDiscount['discount_type'] = 'percentage';
        $cartDiscount['products'][0]['discount'] = 0;
        $cartDiscount['products'][0]['discount_percentage'] = 0;
        $this->mockActionPermission( false );
        $this->json( 'POST', 'api/orders', $productDiscount )->assertForbidden();
        $this->clearUsersServiceMock();
        $this->mockActionPermission( false );
        $this->json( 'POST', 'api/orders', $cartDiscount )->assertForbidden();
    }

    private function mockActionPermission( bool $allowed ): void
    {
        $service = Mockery::mock( UsersService::class );
        $service->shouldReceive( 'isPosActionAllowed' )->andReturn( $allowed );
        $this->app->instance( UsersService::class, $service );
    }

    private function clearUsersServiceMock(): void
    {
        $this->app->forgetInstance( UsersService::class );
    }

    private function orderPayload( Product $product, ProductUnitQuantity $unitQuantity, array $overrides = [] ): array
    {
        return [
            'customer_id' => $this->attemptCreateCustomer()->id,
            'type' => [ 'identifier' => 'takeaway' ],
            'payment_status' => 'hold',
            'shipping' => 0,
            'discount' => 0,
            'discount_percentage' => 0,
            'products' => [
                array_merge( [
                    'product_id' => $product->id,
                    'unit_quantity_id' => $unitQuantity->id,
                    'quantity' => 1,
                    'unit_price' => $unitQuantity->sale_price,
                    'discount' => 0,
                    'discount_percentage' => 0,
                ], $overrides ),
            ],
        ];
    }

    private function createUserRoleUser(): User
    {
        $userRole = Role::namespace( Role::USER );
        $previousRegistrationRole = ns()->option->get( 'ns_registration_role' );
        ns()->option->set( 'ns_registration_role', $userRole->id );
        ns()->option->set( 'ns_registration_validated', 'no' );

        $result = app( UsersService::class )->setUser( [
            'username' => 'authorization_test_' . Str::random( 8 ),
            'email' => 'authorization_test_' . Str::random( 8 ) . '@nexopos-test.invalid',
            'password' => Str::random( 16 ),
            'active' => true,
        ] );

        ns()->option->set( 'ns_registration_role', $previousRegistrationRole );

        return $result['data']['user'];
    }
}
