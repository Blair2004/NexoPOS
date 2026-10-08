<?php

namespace Tests\Unit;

use App\Exceptions\NotAllowedException;
use App\Services\ModulesService;
use App\Services\UpdateService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class MigrationExecutionSecurityTest extends TestCase
{
    public function test_core_migration_must_be_pending(): void
    {
        $service = new class extends UpdateService
        {
            public function getMigrations( $ignoreMigrations = false, $directories = [ 'create', 'update', 'core' ] ): Collection
            {
                return collect( [ 'PendingMigration' ] );
            }
        };

        $this->expectException( NotAllowedException::class );

        $service->executePendingMigrationFromFileName( 'AlreadyExecutedMigration' );
    }

    public function test_module_migration_must_be_pending(): void
    {
        $service = Mockery::mock( ModulesService::class )->makePartial();
        $service->shouldReceive( 'getMigrations' )
            ->once()
            ->with( 'Example' )
            ->andReturn( [] );

        $this->expectException( NotAllowedException::class );

        $service->runMigration( 'Example', 'Example/Migrations/CreateExampleTable.php' );
    }

    public function test_module_migration_cannot_escape_its_migrations_directory(): void
    {
        $file = '../tests/TestCase.php';
        $service = Mockery::mock( ModulesService::class )->makePartial();
        $service->shouldReceive( 'getMigrations' )
            ->once()
            ->with( 'Example' )
            ->andReturn( [ $file ] );

        $this->expectException( NotAllowedException::class );

        $service->runMigration( 'Example', $file );
    }
}
