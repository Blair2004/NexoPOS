<?php

namespace Tests\Feature\NsRacksManager;

use App\Classes\Schema as NsSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\NsRacksManager\Migrations\RenameAuthorToAuthorIdOnRacksTables;
use Modules\NsRacksManager\Models\Rack;
use Modules\NsRacksManager\Models\RackArea;
use Modules\NsRacksManager\Models\RackHistory;
use Tests\TestCase;

class RenameAuthorToAuthorIdTest extends TestCase
{
    /**
     * @var list<string>
     */
    protected array $tables = [
        'nexopos_racks',
        'nexopos_racks_areas',
        'nexopos_racks_history',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ( $this->tables as $table ) {
            Schema::dropIfExists( $table );
        }

        NsSchema::createIfMissing( 'nexopos_racks', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->integer( 'racks_area_id' )->default( 0 );
            $table->float( 'total_products' )->default( 0 );
            $table->text( 'description' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_racks_areas', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->text( 'description' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_racks_history', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'operation' );
            $table->float( 'previous_quantity' )->default( 0 );
            $table->float( 'quantity' )->default( 0 );
            $table->float( 'next_quantity' )->default( 0 );
            $table->integer( 'product_id' )->default( 0 );
            $table->integer( 'unit_id' )->default( 0 );
            $table->integer( 'rack_id' )->default( 0 );
            $table->integer( 'author' );
            $table->text( 'description' )->nullable();
            $table->timestamps();
        } );
    }

    public function test_migration_renames_author_to_author_id_on_rack_tables(): void
    {
        foreach ( $this->tables as $table ) {
            $this->assertTrue( Schema::hasColumn( $table, 'author' ) );
            $this->assertFalse( Schema::hasColumn( $table, 'author_id' ) );
        }

        ( new RenameAuthorToAuthorIdOnRacksTables )->up();

        foreach ( $this->tables as $table ) {
            $this->assertFalse( Schema::hasColumn( $table, 'author' ) );
            $this->assertTrue( Schema::hasColumn( $table, 'author_id' ) );
        }

        $this->assertSame( 'author_id', ( new Rack )->author()->getForeignKeyName() );
        $this->assertSame( 'author_id', ( new RackArea )->author()->getForeignKeyName() );
        $this->assertSame( 'author_id', ( new RackHistory )->author()->getForeignKeyName() );
    }

    public function test_migration_is_idempotent_when_author_id_already_exists(): void
    {
        ( new RenameAuthorToAuthorIdOnRacksTables )->up();
        ( new RenameAuthorToAuthorIdOnRacksTables )->up();

        foreach ( $this->tables as $table ) {
            $this->assertFalse( Schema::hasColumn( $table, 'author' ) );
            $this->assertTrue( Schema::hasColumn( $table, 'author_id' ) );
        }
    }
}
