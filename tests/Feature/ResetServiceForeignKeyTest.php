<?php

namespace Tests\Feature;

use App\Services\ResetService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResetServiceForeignKeyTest extends TestCase
{
    public function test_it_truncates_tables_that_reference_each_other(): void
    {
        Schema::dropIfExists( 'reset_service_children' );
        Schema::dropIfExists( 'reset_service_parents' );

        try {
            Schema::create( 'reset_service_parents', function ( Blueprint $table ): void {
                $table->id();
            } );

            Schema::create( 'reset_service_children', function ( Blueprint $table ): void {
                $table->id();
                $table->foreignId( 'parent_id' )
                    ->constrained( 'reset_service_parents' )
                    ->restrictOnDelete();
            } );

            Schema::enableForeignKeyConstraints();

            DB::table( 'reset_service_parents' )->insert( ['id' => 1] );
            DB::table( 'reset_service_children' )->insert( [
                'id' => 1,
                'parent_id' => 1,
            ] );

            $resetService = new class extends ResetService
            {
                /**
                 * @param array<int, string> $tables
                 */
                public function truncateOnly( array $tables ): void
                {
                    $this->truncateTables( $tables );
                }
            };

            $resetService->truncateOnly( [
                'reset_service_parents',
                'reset_service_children',
            ] );

            $this->assertSame( 0, DB::table( 'reset_service_parents' )->count() );
            $this->assertSame( 0, DB::table( 'reset_service_children' )->count() );

            $this->expectException( QueryException::class );
            DB::table( 'reset_service_children' )->insert( [
                'id' => 2,
                'parent_id' => 999,
            ] );
        } finally {
            Schema::dropIfExists( 'reset_service_children' );
            Schema::dropIfExists( 'reset_service_parents' );
        }
    }
}
