<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\NsAppointments\Models\Appointment;
use Modules\NsAppointments\Models\AppointmentService;
use Modules\NsAppointments\Models\AppointmentWorker;
use Modules\NsAppointments\Services\AppointmentCatalogService;
use Modules\NsAppointments\Services\AppointmentCategoryStaffAssignmentService;
use Modules\NsAppointments\Services\AppointmentOptions;
use Modules\NsAppointments\Services\AppointmentSchedulingService;
use Modules\NsAppointments\Services\AppointmentStaffService;
use Tests\TestCase;

class PublicBookingFlowTest extends TestCase
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

        Carbon::setTestNow( '2026-07-23 08:00:00' );
        $this->ensureCoreTablesExist();

        $setupMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000000_create_nsappointments_tables.php' );
        $appointmentMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000000_create_nsappointments_appointment_records.php' );
        $publicFieldsMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_000001_extend_nsappointments_public_booking_fields.php' );
        $roleMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_203937_create_nsappointments_category_roles_and_worker_availability.php' );

        $setupMigration->up();
        $appointmentMigration->up();
        $publicFieldsMigration->up();
        $roleMigration->up();

        DB::table( 'nexopos_appointment_items' )->delete();
        DB::table( 'nexopos_appointments' )->delete();
        DB::table( 'nexopos_appointment_category_roles' )->delete();
        DB::table( 'nexopos_appointment_category_workers' )->delete();
        DB::table( 'nexopos_appointment_services' )->delete();
        DB::table( 'nexopos_appointment_workers' )->delete();
        DB::table( 'nexopos_products_unit_quantities' )->delete();
        DB::table( 'nexopos_products' )->delete();
        DB::table( 'nexopos_products_categories' )->delete();
        DB::table( 'nexopos_users_roles_relations' )->delete();
        DB::table( 'nexopos_roles' )->delete();
        DB::table( 'nexopos_users' )->delete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_catalog_lists_only_products_from_service_categories(): void
    {
        $serviceCategoryId = $this->createCategory( 'Massage' );
        $retailCategoryId = $this->createCategory( 'Retail' );

        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$serviceCategoryId] );

        $this->createProductWithUnit( 'Deep Tissue Massage', $serviceCategoryId, 75 );
        $this->createProductWithUnit( 'Shampoo Bottle', $retailCategoryId, 18 );

        $catalog = app( AppointmentCatalogService::class )->catalog();

        $this->assertCount( 1, $catalog[ 'services' ] );
        $this->assertSame( 'Deep Tissue Massage', $catalog[ 'services' ]->first()[ 'name' ] );
    }

    public function test_scheduling_generates_slots_for_bookable_services(): void
    {
        $serviceCategoryId = $this->createCategory( 'Facials' );
        $productId = $this->createProductWithUnit( 'Express Facial', $serviceCategoryId, 55 );

        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$serviceCategoryId] );
        ns()->option->set( AppointmentOptions::BusinessDays, [4] );
        ns()->option->set( AppointmentOptions::BusinessOpenTime, '09:00' );
        ns()->option->set( AppointmentOptions::BusinessCloseTime, '10:00' );
        ns()->option->set( AppointmentOptions::MinimumNoticeMinutes, 0 );
        ns()->option->set( AppointmentOptions::MaximumBookingDays, 1 );
        ns()->option->set( AppointmentOptions::SlotIntervalMinutes, 30 );

        AppointmentService::create( [
            'product_id' => $productId,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'payment_requirement' => 'partial',
            'deposit_type' => 'fixed',
            'deposit_value' => 10,
            'is_active' => true,
        ] );
        $this->createAssignedWorkerForCategory( $serviceCategoryId, 'Front Staff' );

        $slots = app( AppointmentSchedulingService::class )->availableSlots(
            items: [
                [ 'product_id' => $productId ],
            ],
            date: '2026-07-23'
        );

        $this->assertCount( 2, $slots );
        $this->assertSame( '2026-07-23 09:00:00', $slots[0][ 'starts_at' ] );
        $this->assertSame( 'Front Staff', $slots[0][ 'workers' ][0][ 'name' ] );
    }

    public function test_product_context_exposes_only_category_assigned_staff(): void
    {
        $serviceCategoryId = $this->createCategory( 'Massage' );
        $retailCategoryId = $this->createCategory( 'Retail' );
        $serviceProductId = $this->createProductWithUnit( 'Massage', $serviceCategoryId, 75 );
        $retailProductId = $this->createProductWithUnit( 'Oil', $retailCategoryId, 12 );

        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$serviceCategoryId] );

        AppointmentService::create( [
            'product_id' => $serviceProductId,
            'duration_minutes' => 60,
            'is_active' => true,
        ] );

        $worker = $this->createAssignedWorkerForCategory( $serviceCategoryId, 'Massage Staff' );
        $catalog = app( AppointmentCatalogService::class );
        $serviceContext = $catalog->productContext( Product::findOrFail( $serviceProductId ) );
        $retailContext = $catalog->productContext( Product::findOrFail( $retailProductId ) );

        $this->assertTrue( $serviceContext['is_service'] );
        $this->assertSame( $worker->id, $serviceContext['staff']->first()['id'] );
        $this->assertFalse( $retailContext['is_service'] );
    }

    public function test_available_workers_excludes_staff_with_an_overlapping_appointment(): void
    {
        $serviceCategoryId = $this->createCategory( 'Massage' );
        $serviceProductId = $this->createProductWithUnit( 'Massage', $serviceCategoryId, 75 );

        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$serviceCategoryId] );

        AppointmentService::create( [
            'product_id' => $serviceProductId,
            'duration_minutes' => 60,
            'is_active' => true,
        ] );

        $worker = $this->createAssignedWorkerForCategory( $serviceCategoryId, 'Massage Staff' );
        $scheduling = app( AppointmentSchedulingService::class );
        $product = Product::findOrFail( $serviceProductId );

        $this->assertSame(
            [$worker->id],
            array_column( $scheduling->availableWorkers( $product, '2026-07-23 10:00:00' ), 'id' )
        );

        Appointment::create( [
            'reference' => 'APT-WORKER-CONFLICT',
            'worker_id' => $worker->id,
            'starts_at' => '2026-07-23 11:30:00',
            'ends_at' => '2026-07-23 12:30:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_DASHBOARD,
            'payment_status' => 'unpaid',
        ] );

        $this->assertSame( [$worker->id], array_column(
            $scheduling->availableWorkers( $product, '2026-07-23 10:00:00', null, 1 ),
            'id'
        ) );
        $this->assertSame( [], $scheduling->availableWorkers( $product, '2026-07-23 10:00:00', null, 2 ) );
    }

    public function test_scheduling_rejects_products_outside_service_categories(): void
    {
        $serviceCategoryId = $this->createCategory( 'Services' );
        $retailCategoryId = $this->createCategory( 'Retail' );
        $productId = $this->createProductWithUnit( 'Retail Product', $retailCategoryId, 20 );

        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$serviceCategoryId] );

        $this->expectException( ValidationException::class );

        app( AppointmentSchedulingService::class )->availableSlots(
            items: [
                [ 'product_id' => $productId ],
            ],
            date: '2026-07-23'
        );
    }

    private function createCategory( string $name ): int
    {
        return DB::table( 'nexopos_products_categories' )->insertGetId( [
            'name' => $name,
            'parent_id' => 0,
            'displays_on_pos' => true,
            'total_items' => 0,
            'author_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ] );
    }

    private function createProductWithUnit( string $name, int $categoryId, float $price ): int
    {
        $productId = DB::table( 'nexopos_products' )->insertGetId( [
            'name' => $name,
            'description' => $name . ' description',
            'category_id' => $categoryId,
            'status' => Product::STATUS_AVAILABLE,
            'type' => Product::TYPE_DEMATERIALIZED,
            'product_type' => 'product',
            'stock_management' => Product::STOCK_MANAGEMENT_DISABLED,
            'accurate_tracking' => false,
            'auto_cogs' => false,
            'tax_type' => 'inclusive',
            'tax_group_id' => 0,
            'tax_value' => 0,
            'barcode' => null,
            'barcode_type' => null,
            'sku' => null,
            'thumbnail_id' => 0,
            'parent_id' => 0,
            'unit_group' => 1,
            'on_expiration' => Product::EXPIRES_ALLOW_SALES,
            'expires' => false,
            'searchable' => true,
            'author_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        DB::table( 'nexopos_products_unit_quantities' )->insert( [
            'product_id' => $productId,
            'unit_id' => $this->unitId(),
            'visible' => true,
            'sale_price' => $price,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        return $productId;
    }

    private function createAssignedWorkerForCategory( int $categoryId, string $displayName ): AppointmentWorker
    {
        $suffix = uniqid();
        $roleId = DB::table( 'nexopos_roles' )->insertGetId( [
            'name' => 'Appointment Staff ' . $suffix,
            'namespace' => 'ns-appointments-staff-' . $suffix,
            'description' => 'Appointment staff',
            'author_id' => 1,
            'locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ] );
        $userId = $this->createUser();

        DB::table( 'nexopos_users_roles_relations' )->insert( [
            'role_id' => $roleId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        ns()->option->set( AppointmentOptions::StaffRoleIds, [$roleId] );

        $worker = app( AppointmentStaffService::class )->assignableWorkers()->firstOrFail();
        $worker->display_name = $displayName;
        $worker->save();

        app( AppointmentCategoryStaffAssignmentService::class )->sync( $categoryId, [$roleId] );

        return $worker;
    }

    private function createUser(): int
    {
        return DB::table( 'nexopos_users' )->insertGetId( [
            'username' => 'appointment-worker',
            'email' => 'appointment-worker@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
            'author_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ] );
    }

    private function unitId(): int
    {
        $unit = DB::table( 'nexopos_units' )->first();

        if ( $unit ) {
            return $unit->id;
        }

        return DB::table( 'nexopos_units' )->insertGetId( [
            'name' => 'Each',
            'identifier' => 'each',
            'description' => 'Each',
            'value' => 1,
            'base_unit' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ] );
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

        NsSchema::createIfMissing( 'nexopos_users', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'username' );
            $table->boolean( 'active' )->default( false );
            $table->integer( 'author_id' )->nullable();
            $table->string( 'email' )->unique();
            $table->string( 'password' );
            $table->integer( 'group_id' )->nullable();
            $table->rememberToken();
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_roles', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' )->unique();
            $table->string( 'namespace' )->unique();
            $table->text( 'description' )->nullable();
            $table->integer( 'reward_system_id' )->nullable();
            $table->double( 'minimal_credit_payment' )->default( 0 );
            $table->integer( 'author_id' )->nullable();
            $table->boolean( 'locked' )->default( true );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_users_roles_relations', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->integer( 'role_id' );
            $table->integer( 'user_id' );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_products_categories', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->integer( 'parent_id' )->nullable()->default( 0 );
            $table->string( 'preview_url' )->nullable();
            $table->boolean( 'displays_on_pos' )->default( true );
            $table->integer( 'total_items' )->default( 0 );
            $table->text( 'description' )->nullable();
            $table->integer( 'author_id' );
            $table->string( 'uuid' )->nullable();
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_products', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->string( 'tax_type' )->nullable();
            $table->integer( 'tax_group_id' )->default( 0 );
            $table->float( 'tax_value' )->default( 0 );
            $table->string( 'product_type' )->default( 'product' );
            $table->string( 'type' )->default( Product::TYPE_DEMATERIALIZED );
            $table->boolean( 'accurate_tracking' )->default( false );
            $table->boolean( 'auto_cogs' )->default( false );
            $table->string( 'status' )->default( Product::STATUS_AVAILABLE );
            $table->string( 'stock_management' )->default( Product::STOCK_MANAGEMENT_DISABLED );
            $table->string( 'barcode' )->nullable();
            $table->string( 'barcode_type' )->nullable();
            $table->string( 'sku' )->nullable();
            $table->text( 'description' )->nullable();
            $table->integer( 'thumbnail_id' )->default( 0 );
            $table->integer( 'category_id' )->nullable();
            $table->integer( 'parent_id' )->default( 0 );
            $table->integer( 'unit_group' )->nullable();
            $table->string( 'on_expiration' )->nullable();
            $table->boolean( 'expires' )->default( false );
            $table->boolean( 'searchable' )->default( true );
            $table->integer( 'author_id' );
            $table->string( 'uuid' )->nullable();
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_units', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->string( 'identifier' )->nullable();
            $table->text( 'description' )->nullable();
            $table->integer( 'group_id' )->nullable();
            $table->float( 'value' )->default( 1 );
            $table->boolean( 'base_unit' )->default( false );
            $table->integer( 'author_id' )->nullable();
            $table->string( 'uuid' )->nullable();
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_products_unit_quantities', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->integer( 'product_id' );
            $table->integer( 'unit_id' );
            $table->boolean( 'visible' )->default( true );
            $table->float( 'quantity' )->default( 0 );
            $table->float( 'low_quantity' )->default( 0 );
            $table->boolean( 'stock_alert_enabled' )->default( false );
            $table->float( 'sale_price' )->default( 0 );
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
