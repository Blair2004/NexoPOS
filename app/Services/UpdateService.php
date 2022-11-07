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
     * returns available migrations files names
     * as an array of strings. Might be empty if there is
     * no migration available.
     *
     * @param void
     * @return Collection
     */
    public function getMigrations( $ignoreMigrations = false ): Collection
    {
        /**
         * in case the option ignoreMigration
         * is set to "true".
         */
        $migrations = collect([]);

        if ( $ignoreMigrations === false ) {
            $migrations = Migration::get()->map( fn( $migration ) => $migration->migration );
        }

        $files = collect( Storage::disk( 'ns' )->allFiles( 'database/migrations' ) )
            ->filter( fn( $file ) => pathinfo( $file )[ 'extension' ] === 'php' )
            ->map( function( $file ) {
            $fileInfo = pathinfo( $file );

            return $fileInfo[ 'filename' ];
        });

        return collect( $files )->diff( $migrations );
    }

    public function getMatchingFullPath( $file )
    {
        $files = collect( Storage::disk( 'ns' )->allFiles( 'database/migrations' ) )
            ->filter( fn( $file ) => pathinfo( $file )[ 'extension' ] === 'php' )
            ->mapWithKeys( function( $file ) {
            $fileInfo = pathinfo( $file );

            return [ $fileInfo[ 'filename' ] => $file ];
        });

        return $files[ $file ];
    }

    public function executeMigration( $file, $method = 'up' )
    {
        $pathinfo = pathinfo( $file );
        $type = collect( explode( '/', $pathinfo[ 'dirname' ]) )->last();

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
