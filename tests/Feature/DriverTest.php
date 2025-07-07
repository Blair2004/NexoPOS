<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderDeliveryProof;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\UserAttribute;
use App\Services\DriverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class DriverTest extends TestCase
{
    use WithFaker, WithAuthentication;

    /**
     * A basic feature test example.
     */
    public function test_create_driver_from_users_crud(): void
    {
        $this->attemptAuthenticate();

        $password = $this->faker->password( 8, 20 );
        $role = Role::where( 'namespace', Role::DRIVER )->first();

        $configuration = [
            'username' => $this->faker->username(),
            'general' => [
                'email' => $this->faker->email(),
                    'password' => $password,
                'password_confirm' => $password,
                'roles' => [ $role->id ],
                'active' => 1, // true
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', '/api/crud/ns.users', $configuration );

        $response   =   $response->json();

        $driver   =   Driver::findOrFail( $response[ 'data' ][ 'entry' ][ 'id' ] );

        $this->assertTrue( $driver->status == Driver::STATUS_OFFLINE, 'The default status of a driver should be offline.' );
        $this->assertTrue( UserAttribute::where( 'user_id', $driver->id )->exists(), 'The driver attribute should be created.' );
    }

    public function test_create_driver_from_crud(): void
    {
        $this->attemptAuthenticate();

        $password = $this->faker->password( 8, 20 );
        $role = Role::where( 'namespace', Role::DRIVER )->first();

        $configuration = [
            'username' => $this->faker->username(),
            'general' => [
                'email' => $this->faker->email(),
                'password' => $password,
                'password_confirm' => $password,
                'active' => 1, // true
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', '/api/crud/ns.drivers', $configuration );

        $response   =   $response->json();

        $driver   =   Driver::findOrFail( $response[ 'data' ][ 'entry' ][ 'id' ] );

        $this->assertTrue( $driver instanceof Driver, 'The driver should be an instance of the Driver model.' );
        $this->assertTrue( $driver->status == Driver::STATUS_OFFLINE, 'The default status of a driver should be offline.' );
        $this->assertTrue( UserAttribute::where( 'user_id', $driver->id )->exists(), 'The driver attribute should be created.' );
    }

    public function test_driver_can_update_order_delivery_status(): void
    {
        $this->attemptAuthenticate();

        // Create a driver
        $password = $this->faker->password(8, 20);
        $role = Role::where('namespace', Role::DRIVER)->first();
        
        $driverConfig = [
            'username' => $this->faker->username(),
            'general' => [
                'email' => $this->faker->email(),
                'password' => $password,
                'password_confirm' => $password,
                'roles' => [$role->id],
                'active' => 1,
            ],
        ];

        $driverResponse = $this->withSession($this->app['session']->all())
            ->json('post', '/api/crud/ns.drivers', $driverConfig);

        $driver = Driver::findOrFail($driverResponse->json()['data']['entry']['id']);

        // Create a test order
        $order = $this->createTestOrder();
        $order->driver_id = $driver->id;
        $order->type = Order::TYPE_DELIVERY;
        $order->delivery_status = Order::DELIVERY_ONGOING;
        $order->save();

        // Test update order
        $driverService = app(DriverService::class);
        
        $updateData = [
            'is_delivered' => 1,
            'delivery_proof' => 'https://example.com/proof.jpg',
            'note' => 'Package delivered successfully',
            'driver_id' => $driver->id
        ];

        $result = $driverService->updateOrder($order, $updateData);

        $this->assertTrue($result instanceof \App\Classes\JsonResponse);
        
        // Verify delivery proof was created
        $deliveryProof = OrderDeliveryProof::where('order_id', $order->id)->first();
        $this->assertNotNull($deliveryProof);
        $this->assertTrue($deliveryProof->is_delivered);
        $this->assertEquals('Package delivered successfully', $deliveryProof->note);
        $this->assertEquals('https://example.com/proof.jpg', $deliveryProof->delivery_proof);

        // Verify order status was updated
        $order->refresh();
        $this->assertEquals(Order::DELIVERY_DELIVERED, $order->delivery_status);
    }

    private function createTestOrder(): Order
    {
        // This would typically use your existing order creation logic
        // For now, creating a minimal order for testing
        return Order::create([
            'code' => 'TEST-' . uniqid(),
            'title' => 'Test Order',
            'author' => auth()->id(),
            'customer_id' => 1,
            'total' => 100.00,
            'subtotal' => 90.00,
            'tax_value' => 10.00,
            'payment_status' => Order::PAYMENT_UNPAID,
            'process_status' => Order::PROCESSING_PENDING,
            'delivery_status' => Order::DELIVERY_PENDING,
            'type' => Order::TYPE_DELIVERY,
        ]);
    }

    /**
     * Test driver can start a delivery
     */
    public function test_driver_can_start_delivery(): void
    {
        $this->attemptAuthenticate();
        
        // Create a driver
        $driver = Driver::factory()->create();
        
        // Create a delivery order assigned to this driver
        $order = $this->createTestOrder();
        $order->driver_id = $driver->id;
        $order->delivery_status = Order::DELIVERY_PENDING;
        $order->save();
        
        // Start the delivery
        $driverService = new DriverService();
        $response = $driverService->startDelivery($order, $driver->id);
        
        $this->assertEquals('success', $response->original['status']);
        
        // Verify order status changed
        $order->refresh();
        $this->assertEquals(Order::DELIVERY_ONGOING, $order->delivery_status);
    }

    /**
     * Test driver can reject a delivery
     */
    public function test_driver_can_reject_delivery(): void
    {
        $this->attemptAuthenticate();
        
        // Create a driver
        $driver = Driver::factory()->create();
        
        // Create a delivery order assigned to this driver
        $order = $this->createTestOrder();
        $order->driver_id = $driver->id;
        $order->delivery_status = Order::DELIVERY_PENDING;
        $order->save();
        
        // Reject the delivery
        $driverService = new DriverService();
        $response = $driverService->rejectDelivery($order, $driver->id);
        
        $this->assertEquals('success', $response->original['status']);
        
        // Verify driver is unassigned and status remains pending
        $order->refresh();
        $this->assertNull($order->driver_id);
        $this->assertEquals(Order::DELIVERY_PENDING, $order->delivery_status);
    }

    /**
     * Test driver cannot start delivery that is not pending
     */
    public function test_driver_cannot_start_non_pending_delivery(): void
    {
        $this->attemptAuthenticate();
        
        // Create a driver
        $driver = Driver::factory()->create();
        
        // Create a delivery order with ongoing status
        $order = $this->createTestOrder();
        $order->driver_id = $driver->id;
        $order->delivery_status = Order::DELIVERY_ONGOING;
        $order->save();
        
        // Try to start the delivery
        $driverService = new DriverService();
        $response = $driverService->startDelivery($order, $driver->id);
        
        $this->assertEquals('error', $response->original['status']);
        $this->assertStringContainsString('not in pending status', $response->original['message']);
    }

    /**
     * Test driver cannot start delivery not assigned to them
     */
    public function test_driver_cannot_start_unassigned_delivery(): void
    {
        $this->attemptAuthenticate();
        
        // Create two drivers
        $driver1 = Driver::factory()->create();
        $driver2 = Driver::factory()->create();
        
        // Create a delivery order assigned to driver1
        $order = $this->createTestOrder();
        $order->driver_id = $driver1->id;
        $order->delivery_status = Order::DELIVERY_PENDING;
        $order->save();
        
        // Try to start the delivery with driver2
        $driverService = new DriverService();
        $response = $driverService->startDelivery($order, $driver2->id);
        
        $this->assertEquals('error', $response->original['status']);
        $this->assertStringContainsString('not assigned to this delivery', $response->original['message']);
    }
}
