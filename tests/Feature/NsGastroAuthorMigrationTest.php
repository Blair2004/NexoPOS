<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use ReflectionClass;
use Tests\TestCase;

class NsGastroAuthorMigrationTest extends TestCase
{
    public function test_author_column_rename_migration_targets_expected_tables(): void
    {
        $migration = require base_path( 'modules/NsGastro/Migrations/2026_06_30_000000_rename_author_to_author_id_on_nsgastro_tables.php' );
        $reflection = new ReflectionClass( $migration );
        $tables = $reflection->getMethod( 'tables' )->invoke( $migration );

        $this->assertInstanceOf( Migration::class, $migration );
        $this->assertTrue( $reflection->hasMethod( 'up' ) );
        $this->assertTrue( $reflection->hasMethod( 'down' ) );
        $this->assertSame( [
            'nexopos_gastro_areas',
            'nexopos_gastro_kitchens',
            'nexopos_gastro_modifiers_groups',
            'nexopos_gastro_tables',
            'nexopos_gastro_tables_history',
            'nexopos_gastro_tables_booking_history',
        ], $tables );
    }
}
