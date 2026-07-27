<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\NsAppointments\Models\Appointment;
use Modules\NsAppointments\Services\AppointmentAvailabilityService;
use Tests\TestCase;

class AppointmentCalendarTest extends TestCase
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

        Carbon::setTestNow( '2026-07-15 12:00:00' );
        $this->ensureCoreTablesExist();

        $setupMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000000_create_nsappointments_tables.php' );
        $appointmentMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000000_create_nsappointments_appointment_records.php' );
        $publicFieldsMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000001_extend_nsappointments_public_booking_fields.php' );
        $roomMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_25_000000_create_nsappointments_rooms.php' );

        $setupMigration->up();
        $appointmentMigration->up();
        $publicFieldsMigration->up();
        $roomMigration->up();

        DB::table( 'nexopos_appointment_items' )->delete();
        DB::table( 'nexopos_appointments' )->delete();
        DB::table( 'nexopos_appointment_rooms' )->delete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calendar_events_include_last_day_of_range_and_status_label(): void
    {
        Appointment::create( [
            'reference' => 'APT-CAL-0001',
            'customer_name' => 'Jane Doe',
            'worker_id' => 10,
            'starts_at' => '2026-07-01 09:00:00',
            'ends_at' => '2026-07-01 10:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_DASHBOARD,
            'payment_status' => 'unpaid',
        ] );

        Appointment::create( [
            'reference' => 'APT-CAL-0002',
            'customer_name' => 'John Smith',
            'worker_id' => 11,
            'starts_at' => '2026-07-31 16:30:00',
            'ends_at' => '2026-07-31 17:30:00',
            'status' => Appointment::STATUS_PENDING_CONFIRMATION,
            'source' => Appointment::SOURCE_PUBLIC,
            'payment_status' => 'unpaid',
        ] );

        Appointment::create( [
            'reference' => 'APT-CAL-OUT',
            'customer_name' => 'Outside Range',
            'worker_id' => 12,
            'starts_at' => '2026-08-01 10:00:00',
            'ends_at' => '2026-08-01 11:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_DASHBOARD,
            'payment_status' => 'unpaid',
        ] );

        $service = app( AppointmentAvailabilityService::class );
        $events = $service->calendarEvents(
            Carbon::parse( '2026-07-01' )->startOfDay(),
            Carbon::parse( '2026-07-31' )->endOfDay()
        );

        $this->assertCount( 2, $events );
        $this->assertSame( [ 'APT-CAL-0001', 'APT-CAL-0002' ], $events->pluck( 'reference' )->all() );
        $this->assertSame( 'Jane Doe', $events->first()[ 'title' ] );
        $this->assertSame( 'Confirmed', $events->first()[ 'status_label' ] );
        $this->assertSame( 'Pending Confirmation', $events->last()[ 'status_label' ] );
        $this->assertNotEmpty( $events->first()[ 'edit_url' ] );
        $this->assertStringContainsString( '/dashboard/ns-appointments/appointments/edit/', $events->first()[ 'edit_url' ] );
    }

    public function test_date_only_range_without_end_of_day_excludes_late_last_day_appointments(): void
    {
        Appointment::create( [
            'reference' => 'APT-CAL-EDGE',
            'customer_name' => 'Edge Case',
            'starts_at' => '2026-07-31 23:00:00',
            'ends_at' => '2026-07-31 23:45:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_DASHBOARD,
            'payment_status' => 'unpaid',
        ] );

        $service = app( AppointmentAvailabilityService::class );

        $brokenRange = $service->calendarEvents(
            Carbon::parse( '2026-07-01' ),
            Carbon::parse( '2026-07-31' )
        );

        $fixedRange = $service->calendarEvents(
            Carbon::parse( '2026-07-01' )->startOfDay(),
            Carbon::parse( '2026-07-31' )->endOfDay()
        );

        $this->assertCount( 0, $brokenRange );
        $this->assertCount( 1, $fixedRange );
        $this->assertSame( 'APT-CAL-EDGE', $fixedRange->first()[ 'reference' ] );
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
    }
}
