<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Services\CrudEntry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\NsAppointments\Crud\RoomCrud;
use Modules\NsAppointments\Models\Appointment;
use Modules\NsAppointments\Models\AppointmentItem;
use Modules\NsAppointments\Models\Room;
use Modules\NsAppointments\Services\AppointmentOrderService;
use Modules\NsAppointments\Services\AppointmentRoomService;
use Modules\NsAppointments\Services\AppointmentSchedulingService;
use Tests\TestCase;

class RoomFeatureTest extends TestCase
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

        $appointmentMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000000_create_nsappointments_appointment_records.php' );
        $roomMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_25_000000_create_nsappointments_rooms.php' );
        $roomPriceMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_25_231800_add_price_to_nexopos_appointment_rooms_table.php' );
        $appointmentMigration->up();
        $roomMigration->up();
        $roomPriceMigration->up();

        DB::table( 'nexopos_appointment_items' )->delete();
        DB::table( 'nexopos_appointments' )->delete();
        DB::table( 'nexopos_appointment_rooms' )->delete();
        DB::table( 'nexopos_orders' )->delete();
    }

    public function test_only_available_rooms_are_returned_and_a_busy_room_can_be_freed(): void
    {
        $available = Room::create( [
            'name' => 'Ocean Room',
            'thumbnail' => '/storage/ocean.jpg',
            'description' => 'Quiet treatment room',
            'price' => 0,
        ] );
        $busy = Room::create( [
            'name' => 'Garden Room',
            'status' => Room::STATUS_BUSY,
            'price' => 25.5,
        ] );
        Room::create( [
            'name' => 'Closed Room',
            'status' => Room::STATUS_UNAVAILABLE,
        ] );

        $service = app( AppointmentRoomService::class );
        $rooms = $service->availableRooms();

        $this->assertSame( [ $available->id ], $rooms->pluck( 'id' )->all() );
        $this->assertTrue( $rooms->first()[ 'is_free' ] );
        $this->assertSame( 0.0, $rooms->first()[ 'price' ] );

        $service->setFree( $busy );

        $this->assertSame( Room::STATUS_AVAILABLE, $busy->refresh()->status );
        $this->assertFalse( $busy->is_free );
        $this->assertSame( 25.5, (float) $busy->price );

        $entry = ( new RoomCrud )->setActions( new CrudEntry( $busy->toArray() ) );
        $this->assertSame( 'Available', $entry->status );
        $this->assertSame( $busy->id, $entry->{ '$id' } );
        $this->assertNotSame( 'Free', $entry->price );
    }

    public function test_room_crud_normalizes_price_and_formats_list_values(): void
    {
        $free = Room::create( [
            'name' => 'Free Room',
            'price' => 0,
        ] );
        $paid = Room::create( [
            'name' => 'Premium Room',
            'price' => 40,
        ] );

        $crud = new RoomCrud;

        $freeEntry = $crud->setActions( new CrudEntry( $free->toArray() ) );
        $paidEntry = $crud->setActions( new CrudEntry( $paid->toArray() ) );

        $this->assertSame( 'Free', $freeEntry->price );
        $this->assertNotSame( 'Free', $paidEntry->price );

        $normalized = $crud->filterPostInputs( [
            'name' => 'Suite',
            'thumbnail' => null,
            'description' => null,
            'price' => -5,
            'status' => Room::STATUS_AVAILABLE,
        ] );

        $this->assertEquals( 0, $normalized[ 'price' ] );
    }

    public function test_booking_persists_order_product_room_metadata_and_marks_room_busy(): void
    {
        $room = Room::create( [ 'name' => 'Treatment Room' ] );
        $orderId = DB::table( 'nexopos_orders' )->insertGetId( [
            'code' => 'ORD-BOOKING-ROOM-001',
            'type' => AppointmentOrderService::ORDER_BOOKING,
            'customer_id' => 15,
            'author_id' => 1,
            'payment_status' => Order::PAYMENT_UNPAID,
            'ns_appointment_starts_at' => '2026-07-25 14:00:00',
            'ns_appointment_ends_at' => '2026-07-25 15:00:00',
        ] );
        $order = Order::findOrFail( $orderId );
        $orderProduct = new OrderProduct;
        $orderProduct->id = 42;
        $orderProduct->product_id = 99;
        $orderProduct->quantity = 1;
        $orderProduct->setData( [
            'ns_appointment_service' => true,
            'ns_appointment_worker_id' => 10,
            'ns_appointment_room_id' => $room->id,
        ] );
        $order->setRelation( 'products', collect( [ $orderProduct ] ) );

        $this->mock( AppointmentSchedulingService::class, function ( $mock ) use ( $room ): void {
            $mock->shouldReceive( 'schedule' )
                ->once()
                ->withArgs( fn ( array $items ): bool => $items[0]['room_id'] === $room->id )
                ->andReturn( [
                    'items' => [
                        [
                            'product_id' => 99,
                            'worker_id' => 10,
                            'resource_id' => null,
                            'room_id' => $room->id,
                            'starts_at' => Carbon::parse( '2026-07-25 14:00:00' ),
                            'ends_at' => Carbon::parse( '2026-07-25 15:00:00' ),
                            'duration_minutes' => 60,
                            'buffer_before_minutes' => 0,
                            'buffer_after_minutes' => 0,
                            'payment_requirement' => 'none',
                        ],
                    ],
                    'workers' => [ [ 'id' => 10, 'name' => 'Therapist' ] ],
                    'starts_at' => Carbon::parse( '2026-07-25 14:00:00' ),
                    'ends_at' => Carbon::parse( '2026-07-25 15:00:00' ),
                    'worker_id' => 10,
                    'resource_id' => null,
                ] );
        } );

        $appointment = app( AppointmentOrderService::class )->createFromBookingOrder( $order );

        $this->assertInstanceOf( Appointment::class, $appointment );
        $this->assertSame( $room->id, $appointment->room_id );
        $this->assertSame( Room::STATUS_BUSY, $room->refresh()->status );
        $this->assertDatabaseHas( 'nexopos_appointment_items', [
            'appointment_id' => $appointment->id,
            'order_product_id' => 42,
            'room_id' => $room->id,
        ] );
        $this->assertSame( $room->id, AppointmentItem::firstOrFail()->room_id );
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
