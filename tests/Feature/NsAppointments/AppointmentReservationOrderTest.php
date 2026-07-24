<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use App\Events\OrderAfterCheckPerformedEvent;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\NsAppointments\Listeners\ValidateReservationOrderAvailability;
use Modules\NsAppointments\Models\Appointment;
use Modules\NsAppointments\Models\AppointmentItem;
use Modules\NsAppointments\Services\AppointmentAvailabilityService;
use Modules\NsAppointments\Services\AppointmentOrderService;
use Modules\NsAppointments\Services\AppointmentSchedulingService;
use Tests\TestCase;

class AppointmentReservationOrderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $databasePath = dirname( __DIR__, 3 ) . '/tests/database.sqlite';

        if ( ! file_exists( $databasePath ) ) {
            touch( $databasePath );
        }

        $database = new \PDO( 'sqlite:' . $databasePath );
        $database->exec( 'create table if not exists ns_nexopos_options (id integer primary key autoincrement, user_id integer null, key varchar(255) not null, value text null, expire_on datetime null, array tinyint(1) not null default 0, created_at datetime null, updated_at datetime null)' );
        $database->exec( 'create table if not exists ns_nexopos_permissions (id integer primary key autoincrement, name varchar(255) not null unique, namespace varchar(255) not null unique, description text not null, created_at datetime null, updated_at datetime null)' );
        $database->exec( 'create table if not exists ns_nexopos_modules_migrations (id integer primary key autoincrement, namespace varchar(255) not null, file varchar(255) not null)' );
        $database->exec( 'create table if not exists ns_nexopos_transactions (id integer primary key autoincrement, recurring tinyint(1) not null default 0, active tinyint(1) not null default 0, occurrence varchar(255) null)' );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCoreTablesExist();

        $setupMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000000_create_nsappointments_tables.php' );
        $appointmentMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000000_create_nsappointments_appointment_records.php' );
        $publicFieldsMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000001_extend_nsappointments_public_booking_fields.php' );

        $setupMigration->up();
        $appointmentMigration->up();
        $publicFieldsMigration->up();

        DB::table( 'nexopos_appointment_items' )->delete();
        DB::table( 'nexopos_appointments' )->delete();
        DB::table( 'nexopos_orders' )->delete();
    }

    public function test_availability_rejects_overlapping_staff_or_room_bookings(): void
    {
        Appointment::create( [
            'reference' => 'APT-TEST-0001',
            'worker_id' => 10,
            'resource_id' => 20,
            'starts_at' => '2026-07-23 10:00:00',
            'ends_at' => '2026-07-23 11:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_DASHBOARD,
            'payment_status' => 'unpaid',
        ] );

        $service = app( AppointmentAvailabilityService::class );

        $this->assertFalse( $service->isAvailable( '2026-07-23 10:30:00', '2026-07-23 11:30:00', 10, null ) );
        $this->assertFalse( $service->isAvailable( '2026-07-23 10:30:00', '2026-07-23 11:30:00', null, 20 ) );
        $this->assertTrue( $service->isAvailable( '2026-07-23 10:30:00', '2026-07-23 11:30:00', 11, 21 ) );
    }

    public function test_reservation_order_creates_one_appointment_record(): void
    {
        $orderId = DB::table( 'nexopos_orders' )->insertGetId( [
            'type' => AppointmentOrderService::ORDER_TYPE,
            'customer_id' => 15,
            'payment_status' => Order::PAYMENT_UNPAID,
            'ns_appointment_starts_at' => '2026-07-23 14:00:00',
            'ns_appointment_ends_at' => '2026-07-23 15:00:00',
            'ns_appointment_worker_id' => 10,
            'ns_appointment_resource_id' => 20,
            'ns_appointment_notes' => 'Front desk reservation',
        ] );

        $order = Order::findOrFail( $orderId );
        $orderProduct = new OrderProduct;
        $orderProduct->id = 42;
        $orderProduct->product_id = 99;
        $orderProduct->quantity = 2;
        $orderProduct->setData( [
            'ns_appointment_service' => true,
            'ns_appointment_worker_id' => 10,
            'ns_appointment_worker_ids' => [10, 11],
        ] );
        $order->setRelation( 'products', collect( [$orderProduct] ) );

        $this->mock( AppointmentSchedulingService::class, function ( $mock ): void {
            $mock->shouldReceive( 'schedule' )
                ->once()
                ->withArgs( function ( array $items, string $startsAt, string $staffId, int $resourceId ): bool {
                    $this->assertSame( [10, 11], array_column( $items, 'worker_id' ) );
                    $this->assertSame( '2026-07-23 14:00:00', $startsAt );
                    $this->assertSame( 20, $resourceId );

                    return $staffId === 'any';
                } )
                ->andReturn( [
                    'items' => [
                        [
                            'product_id' => 99,
                            'worker_id' => 10,
                            'resource_id' => 20,
                            'starts_at' => Carbon::parse( '2026-07-23 14:00:00' ),
                            'ends_at' => Carbon::parse( '2026-07-23 14:30:00' ),
                            'duration_minutes' => 30,
                            'buffer_before_minutes' => 0,
                            'buffer_after_minutes' => 0,
                            'payment_requirement' => 'none',
                        ],
                        [
                            'product_id' => 99,
                            'worker_id' => 11,
                            'resource_id' => 20,
                            'starts_at' => Carbon::parse( '2026-07-23 14:30:00' ),
                            'ends_at' => Carbon::parse( '2026-07-23 15:00:00' ),
                            'duration_minutes' => 30,
                            'buffer_before_minutes' => 0,
                            'buffer_after_minutes' => 0,
                            'payment_requirement' => 'none',
                        ],
                    ],
                    'workers' => [
                        [ 'id' => 10, 'name' => 'Front Staff' ],
                        [ 'id' => 11, 'name' => 'Back Staff' ],
                    ],
                    'starts_at' => Carbon::parse( '2026-07-23 14:00:00' ),
                    'ends_at' => Carbon::parse( '2026-07-23 15:00:00' ),
                    'worker_id' => null,
                    'resource_id' => 20,
                ] );
        } );

        $service = app( AppointmentOrderService::class );

        $appointment = $service->createFromReservationOrder( $order );
        $sameAppointment = $service->createFromReservationOrder( $order );

        $this->assertInstanceOf( Appointment::class, $appointment );
        $this->assertSame( $appointment->id, $sameAppointment->id );
        $this->assertSame( 1, Appointment::where( 'order_id', $orderId )->count() );
        $this->assertSame( Appointment::SOURCE_POS, $appointment->source );
        $this->assertNull( $appointment->worker_id );
        $this->assertSame( 20, $appointment->resource_id );
        $this->assertDatabaseHas( 'nexopos_appointment_items', [
            'appointment_id' => $appointment->id,
            'order_product_id' => 42,
            'product_id' => 99,
            'worker_id' => 10,
            'resource_id' => 20,
        ] );
        $this->assertDatabaseHas( 'nexopos_appointment_items', [
            'appointment_id' => $appointment->id,
            'worker_id' => 11,
        ] );
        $this->assertSame( 2, AppointmentItem::where( 'appointment_id', $appointment->id )->count() );
    }

    public function test_order_precheck_expands_merged_service_quantities(): void
    {
        $this->mock( AppointmentSchedulingService::class, function ( $mock ): void {
            $mock->shouldReceive( 'schedule' )
                ->once()
                ->withArgs( function ( array $items, string $startsAt, string $staffId, int $resourceId ): bool {
                    return array_column( $items, 'worker_id' ) === [10, 11]
                        && $startsAt === '2026-07-23 14:00:00'
                        && $staffId === 'any'
                        && $resourceId === 20;
                } )
                ->andReturn( [
                    'ends_at' => Carbon::parse( '2026-07-23 15:00:00' ),
                ] );
        } );

        app( ValidateReservationOrderAvailability::class )->handle( new OrderAfterCheckPerformedEvent( [
            'type' => AppointmentOrderService::ORDER_TYPE,
            'ns_appointment_starts_at' => '2026-07-23 14:00:00',
            'ns_appointment_ends_at' => '2026-07-23 15:00:00',
            'ns_appointment_resource_id' => 20,
            'products' => [
                [
                    'product_id' => 99,
                    'quantity' => 2,
                    'ns_appointment_service' => true,
                    'ns_appointment_worker_ids' => [10, 11],
                ],
            ],
        ], null ) );

        $this->addToAssertionCount( 1 );
    }

    private function ensureCoreTablesExist(): void
    {
        NsSchema::createIfMissing( 'nexopos_options', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->integer( 'user_id' )->nullable();
            $table->string( 'key' );
            $table->text( 'value' )->nullable();
            $table->dateTime( 'expire_on' )->nullable();
            $table->boolean( 'array' )->default( false );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_orders', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'type' )->nullable();
            $table->integer( 'customer_id' )->nullable();
            $table->string( 'payment_status' )->nullable();
        } );
    }
}
