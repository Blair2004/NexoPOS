<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->schema = Schema::connection( $this->getConnection() );
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return config( 'telescope.storage.database.connection' );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'telescope_entries' ) ) {
            $this->schema->create( 'telescope_entries', function ( Blueprint $table ) {
                $table->bigIncrements( 'sequence' );
                $table->uuid( 'uuid' );
                $table->uuid( 'batch_id' );
                $table->string( 'family_hash' )->nullable();
                $table->boolean( 'should_display_on_index' )->default( true );
                $table->string( 'type', 20 );
                $table->longText( 'content' );
                $table->dateTime( 'created_at' )->nullable();

                $table->unique( 'uuid' );
                $table->index( 'batch_id' );
                $table->index( 'family_hash' );
                $table->index( 'created_at' );
                $table->index( ['type', 'should_display_on_index'] );
            } );
        }

        if ( ! Schema::hasTable( 'telescope_entries_tags' ) ) {
            $this->schema->create( 'telescope_entries_tags', function ( Blueprint $table ) {
                $table->uuid( 'entry_uuid' );
                $table->string( 'tag' );

                $table->index( ['entry_uuid', 'tag'] );
                $table->index( 'tag' );

                $table->foreign( 'entry_uuid' )
                    ->references( 'uuid' )
                    ->on( 'telescope_entries' )
                    ->onDelete( 'cascade' );
            } );
        }

        if ( ! Schema::hasTable( 'telescope_monitoring' ) ) {
            $this->schema->create( 'telescope_monitoring', function ( Blueprint $table ) {
                $table->string( 'tag' );
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->dropIfExists( 'telescope_entries_tags' );
        $this->schema->dropIfExists( 'telescope_entries' );
        $this->schema->dropIfExists( 'telescope_monitoring' );
    }
};
