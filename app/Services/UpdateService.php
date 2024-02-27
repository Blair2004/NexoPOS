<?php

namespace App\Services;

use App\Models\Migration;
use Exception;
use Illuminate\Database\Migrations\Migration as MigrationsMigration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UpdateService
{
    /**
     * returns not yet migrated files as an array of strings.
     * Might be empty if all migrations has run or no migration is available.
     *
     * @param bool $ignoreMigrations
     */
    public function getMigrations( $ignoreMigrations = false, $directories = [ 'create', 'update', 'core' ] ): Collection
    {
        /**
         * in case the option ignoreMigration
         * is set to "true".
         */
        $migrations = collect( [] );

        if ( $ignoreMigrations === false ) {
            $migrations = Migration::get()->map( fn( $migration ) => $migration->migration );
        }

        return collect( $directories )->map( function ( $directory ) {
            $files = collect( Storage::disk( 'ns' )->allFiles( 'database/migrations/' . $directory ) )
                ->filter( fn( $file ) => pathinfo( $file )[ 'extension' ] === 'php' )
                ->map( function ( $file ) {
                    $fileInfo = pathinfo( $file );

                    return $fileInfo[ 'filename' ];
                } );

            return $files;
        } )->flatten()->diff( $migrations );
    }

    /**
     * execute a files by pulling the full path
     * in order to identifiy the migration type
     */
    public function executeMigrationFromFileName( string $file ): void
    {
        $file = $this->getMatchingFullPath( $file );
        $this->executeMigration( $file, 'up' );
    }

    /**
     * Will mark migration file as
     * executed while it might have not been executed
     */
    public function assumeExecuted( string $file )
    {
        $file = $this->getMatchingFullPath( $file );
        $pathinfo = pathinfo( $file );
        $type = collect( explode( '/', $pathinfo[ 'dirname' ] ) )->last();

        $class = require base_path( $file );

        if ( $class instanceof MigrationsMigration ) {
            $migration = new Migration;
            $migration->migration = $pathinfo[ 'filename' ];
            $migration->type = $type;
            $migration->batch = 0;
            $migration->save();

            return $migration;
        }

        throw new Exception( 'Unsupported class provided for the migration.' );
    }

    public function getMatchingFullPath( $file )
    {
        $files = collect( Storage::disk( 'ns' )->allFiles( 'database/migrations' ) )
            ->filter( fn( $file ) => pathinfo( $file )[ 'extension' ] === 'php' )
            ->mapWithKeys( function ( $file ) {
                $fileInfo = pathinfo( $file );

                return [ $fileInfo[ 'filename' ] => $file ];
            } );

        return $files[ $file ];
    }

    public function executeMigration( $file, $method = 'up' )
    {
        $pathinfo = pathinfo( $file );
        $type = collect( explode( '/', $pathinfo[ 'dirname' ] ) )->last();

        $class = require base_path( $file );

        if ( $class instanceof MigrationsMigration ) {
            $class->$method();
            $migration = new Migration;
            $migration->migration = $pathinfo[ 'filename' ];
            $migration->type = $type;
            $migration->batch = 0;
            $migration->save();

            return $migration;
        }

        throw new Exception( 'Unsupported class provided for the migration.' );
    }
}
