<?php

namespace Tests\Feature\NexoAppointments;

use App\Classes\Schema as NsSchema;
use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\NexoAppointments\Models\AppointmentWorker;
use Modules\NexoAppointments\Services\ProductCategoryStaffAssignmentService;
use Tests\TestCase;

class ProductCategoryStaffAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NsSchema::createIfMissing( 'nexopos_products_categories', function ( Blueprint $table ): void {
            $table->id();
            $table->string( 'name' );
            $table->text( 'description' )->nullable();
            $table->boolean( 'displays_on_pos' )->default( true );
            $table->unsignedBigInteger( 'author_id' )->nullable();
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_appointment_workers', function ( Blueprint $table ): void {
            $table->id();
            $table->unsignedBigInteger( 'user_id' )->unique();
            $table->string( 'display_name' );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_appointment_worker_categories', function ( Blueprint $table ): void {
            $table->id();
            $table->unsignedBigInteger( 'worker_id' );
            $table->unsignedBigInteger( 'category_id' );
            $table->timestamps();
        } );
    }

    public function test_it_syncs_workers_for_a_category(): void
    {
        $category = ProductCategory::create( [
            'name' => 'Spa Services',
            'description' => 'Service categories for spa appointments',
            'displays_on_pos' => true,
            'author_id' => 1,
        ] );

        $firstWorker = AppointmentWorker::create( [
            'user_id' => 11,
            'display_name' => 'Alice',
            'is_active' => true,
        ] );

        $secondWorker = AppointmentWorker::create( [
            'user_id' => 12,
            'display_name' => 'Beatrice',
            'is_active' => true,
        ] );

        $service = app( ProductCategoryStaffAssignmentService::class );

        $service->sync( $category->id, [ $firstWorker->id, $secondWorker->id ] );

        $this->assertDatabaseHas( 'nexopos_appointment_worker_categories', [
            'worker_id' => $firstWorker->id,
            'category_id' => $category->id,
        ] );

        $this->assertDatabaseHas( 'nexopos_appointment_worker_categories', [
            'worker_id' => $secondWorker->id,
            'category_id' => $category->id,
        ] );

        $service->sync( $category->id, [ $secondWorker->id ] );

        $this->assertDatabaseMissing( 'nexopos_appointment_worker_categories', [
            'worker_id' => $firstWorker->id,
            'category_id' => $category->id,
        ] );

        $this->assertSame( [ $secondWorker->id ], $service->getWorkerIdsForCategory( $category->id ) );
    }
}
