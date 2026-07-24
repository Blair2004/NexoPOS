<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\NsAppointments\Crud\AppointmentWorkerCrud;
use Modules\NsAppointments\Filters\ProductCategoryStaffFormFilter;
use Modules\NsAppointments\Models\AppointmentWorker;
use Modules\NsAppointments\Services\AppointmentCategoryStaffAssignmentService;
use Modules\NsAppointments\Services\AppointmentOptions;
use Modules\NsAppointments\Services\AppointmentStaffService;
use Modules\NsAppointments\Settings\NsAppointmentsSettings;
use Tests\TestCase;

class CategoryStaffAssignmentTest extends TestCase
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

        $this->ensureCoreDependencyTablesExist();

        $migration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000000_create_nsappointments_tables.php' );
        $roleMigration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_203937_create_nsappointments_category_roles_and_worker_availability.php' );

        $migration->up();
        $roleMigration->up();

        DB::table( 'nexopos_appointment_category_roles' )->delete();
        DB::table( 'nexopos_appointment_category_workers' )->delete();
        DB::table( 'nexopos_appointment_workers' )->delete();
        ns()->option->set( AppointmentOptions::StaffRoleIds, [] );
        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [] );
    }

    public function test_it_exposes_role_backed_users_as_assignable_workers(): void
    {
        $role = $this->createRole( 'ns-appointments-staff-' . uniqid() );
        $user = $this->createUser( 'service-provider' );

        DB::table( 'nexopos_users_roles_relations' )->insert( [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ] );

        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id] );

        $workers = app( AppointmentStaffService::class )->assignableWorkers();

        $this->assertCount( 1, $workers );
        $this->assertSame( $user->id, $workers->first()->user_id );
    }

    public function test_category_roles_automatically_include_new_role_users(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Massage',
            'author_id' => 1,
        ] );
        $role = $this->createRole( 'ns-appointments-therapist-' . uniqid() );
        $otherRole = $this->createRole( 'ns-appointments-reception-' . uniqid() );

        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id, $otherRole->id] );
        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$category->id] );
        app( AppointmentCategoryStaffAssignmentService::class )->sync( $category->id, [$role->id] );

        $firstUser = $this->createUser( 'therapist' );
        $this->assignRole( $firstUser, $role );

        $this->assertSame(
            [$firstUser->id],
            app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id )->pluck( 'user_id' )->all()
        );

        $secondUser = $this->createUser( 'new-therapist' );
        $otherUser = $this->createUser( 'receptionist' );
        $this->assignRole( $secondUser, $role );
        $this->assignRole( $otherUser, $otherRole );

        $this->assertEqualsCanonicalizing(
            [$firstUser->id, $secondUser->id],
            app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id )->pluck( 'user_id' )->all()
        );

        $secondUser->update( ['active' => false] );
        $this->assertSame(
            [$firstUser->id],
            app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id )->pluck( 'user_id' )->all()
        );

        DB::table( 'nexopos_users_roles_relations' )
            ->where( 'user_id', $firstUser->id )
            ->where( 'role_id', $role->id )
            ->delete();
        $this->assertCount( 0, app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id ) );
    }

    public function test_category_assignment_rejects_roles_outside_the_staff_allowlist(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Facial',
            'author_id' => 1,
        ] );
        $allowedRole = $this->createRole( 'ns-appointments-facial-' . uniqid() );
        $otherRole = $this->createRole( 'ns-appointments-other-' . uniqid() );

        ns()->option->set( AppointmentOptions::StaffRoleIds, [$allowedRole->id] );
        app( AppointmentCategoryStaffAssignmentService::class )->sync( $category->id, [
            $allowedRole->id,
            $otherRole->id,
        ] );

        $this->assertSame(
            [$allowedRole->id],
            app( AppointmentCategoryStaffAssignmentService::class )->roleIdsForCategory( $category->id )
        );
    }

    public function test_manual_worker_availability_controls_role_eligibility(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Hair',
            'author_id' => 1,
        ] );
        $role = $this->createRole( 'ns-appointments-barber-' . uniqid() );
        $user = $this->createUser( 'barber' );
        $this->assignRole( $user, $role );

        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id] );
        app( AppointmentCategoryStaffAssignmentService::class )->sync( $category->id, [$role->id] );

        $worker = app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id )->firstOrFail();

        foreach ( ['unavailable', 'busy'] as $status ) {
            $worker->update( ['availability_status' => $status] );
            $this->assertCount( 0, app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id ) );
        }

        $worker->update( ['availability_status' => 'available'] );
        $this->assertCount( 1, app( AppointmentStaffService::class )->assignedWorkersForCategory( $category->id ) );
    }

    public function test_migration_converts_only_unambiguous_legacy_worker_assignments(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Legacy Massage',
            'author_id' => 1,
        ] );
        $role = $this->createRole( 'ns-appointments-legacy-' . uniqid() );
        $user = $this->createUser( 'legacy-worker' );
        $this->assignRole( $user, $role );
        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id] );

        $worker = AppointmentWorker::create( [
            'user_id' => $user->id,
            'display_name' => 'Legacy Worker',
            'is_active' => true,
            'availability_status' => AppointmentWorker::AVAILABILITY_AVAILABLE,
        ] );
        DB::table( 'nexopos_appointment_category_workers' )->insert( [
            'category_id' => $category->id,
            'worker_id' => $worker->id,
        ] );

        $migration = require base_path( 'modules/NsAppointments/Migrations/2026_07_23_203937_create_nsappointments_category_roles_and_worker_availability.php' );
        $migration->up();

        $this->assertDatabaseHas( 'nexopos_appointment_category_roles', [
            'category_id' => $category->id,
            'role_id' => $role->id,
        ] );

        $ambiguousCategory = ProductCategory::create( [
            'name' => 'Ambiguous Service',
            'author_id' => 1,
        ] );
        $secondRole = $this->createRole( 'ns-appointments-legacy-second-' . uniqid() );
        $this->assignRole( $user, $secondRole );
        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id, $secondRole->id] );
        DB::table( 'nexopos_appointment_category_workers' )->insert( [
            'category_id' => $ambiguousCategory->id,
            'worker_id' => $worker->id,
        ] );

        $migration->up();

        $this->assertDatabaseMissing( 'nexopos_appointment_category_roles', [
            'category_id' => $ambiguousCategory->id,
        ] );
    }

    public function test_worker_crud_exposes_manual_availability_controls(): void
    {
        $worker = new AppointmentWorker( [
            'display_name' => 'Front Desk Barber',
            'is_active' => true,
            'availability_status' => AppointmentWorker::AVAILABILITY_BUSY,
        ] );
        $worker->id = 10;
        $crud = app( AppointmentWorkerCrud::class );
        $form = $crud->getForm( $worker );
        $filtered = $crud->filterPutInputs( [
            'display_name' => 'Updated Barber',
            'is_active' => false,
            'availability_status' => AppointmentWorker::AVAILABILITY_UNAVAILABLE,
            'user_id' => 999,
        ], $worker );

        $this->assertSame( AppointmentWorker::AVAILABILITY_BUSY, $form['tabs']['availability']['fields'][0]['value'] );
        $this->assertSame( AppointmentWorker::availabilityOptions(), collect( $form['tabs']['availability']['fields'][0]['options'] )->pluck( 'label', 'value' )->all() );
        $this->assertArrayNotHasKey( 'user_id', $filtered );
        $this->assertSame( AppointmentWorker::AVAILABILITY_UNAVAILABLE, $filtered['availability_status'] );
    }

    public function test_it_adds_staff_multiselect_to_service_category_forms(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Facial',
            'author_id' => 1,
        ] );

        $role = $this->createRole( 'ns-appointments-facial-role-' . uniqid() );
        ns()->option->set( AppointmentOptions::StaffRoleIds, [$role->id] );
        ns()->option->set( AppointmentOptions::ServiceCategoryIds, [$category->id] );

        $form = app( ProductCategoryStaffFormFilter::class )->handle( [
            'tabs' => [],
        ], $category );

        $this->assertArrayHasKey( 'ns_appointments_staff', $form['tabs'] );
        $this->assertSame( 'role_ids', $form['tabs']['ns_appointments_staff']['fields'][0]['name'] );
        $this->assertSame( $role->id, $form['tabs']['ns_appointments_staff']['fields'][0]['options'][0]['value'] );
        $this->assertSame( 'multiselect', $form['tabs']['ns_appointments_staff']['fields'][0]['type'] );
    }

    public function test_it_rejects_overlapping_category_classification(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Retail',
            'author_id' => 1,
        ] );

        $this->expectException( ValidationException::class );

        app( AppointmentOptions::class )->validateDistinctCategoryTypes( [
            AppointmentOptions::SellableCategoryIds => [$category->id],
            AppointmentOptions::ServiceCategoryIds => [$category->id],
            AppointmentOptions::ResourceCategoryIds => [],
        ] );
    }

    public function test_settings_validation_rules_are_scoped_to_their_tabs(): void
    {
        $rules = app( NsAppointmentsSettings::class )->validateForm( request() );

        $this->assertArrayHasKey( 'catalog.' . AppointmentOptions::ServiceCategoryIds, $rules );
        $this->assertArrayHasKey( 'catalog.' . AppointmentOptions::ServiceCategoryIds . '.*', $rules );
        $this->assertArrayHasKey( 'staff.' . AppointmentOptions::StaffRoleIds, $rules );
        $this->assertArrayHasKey( 'booking.' . AppointmentOptions::PublicBookingEnabled, $rules );
        $this->assertArrayNotHasKey( AppointmentOptions::PublicBookingEnabled, $rules );
        $this->assertArrayNotHasKey( AppointmentOptions::ServiceCategoryIds, $rules );
    }

    private function createRole( string $namespace ): Role
    {
        return Role::firstOrCreate( [
            'namespace' => $namespace,
        ], [
            'name' => $namespace,
            'description' => 'Appointment staff',
        ] );
    }

    private function assignRole( User $user, Role $role ): void
    {
        DB::table( 'nexopos_users_roles_relations' )->insert( [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ] );
    }

    private function createUser( string $name ): User
    {
        $suffix = uniqid();

        return User::create( [
            'username' => $name . '-' . $suffix,
            'email' => $name . '-' . $suffix . '@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
            'author_id' => 1,
        ] );
    }

    private function ensureCoreDependencyTablesExist(): void
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

        NsSchema::createIfMissing( 'nexopos_permissions', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' )->unique();
            $table->string( 'namespace' )->unique();
            $table->text( 'description' );
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
    }
}
