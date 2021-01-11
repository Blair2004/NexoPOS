<?php
namespace App\Services;

use App\Models\Migration;
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
    public function getMigrations(): Collection
    {
        $migrations         =   Migration::get()->map( fn( $migration ) => $migration->migration );

        $files              =   collect( Storage::disk( 'ns' )->allFiles( 'database/migrations' ) )->map( function( $file ) {
            $fileInfo       =   pathinfo( $file );
            return $fileInfo[ 'filename' ];
        });

        return collect( $files )->diff( $migrations );
    }

    public function getMatchingFullPath( $file )
    {
        $files              =   collect( Storage::disk( 'ns' )->allFiles( 'database/migrations' ) )->mapWithKeys( function( $file ) {
            $fileInfo       =   pathinfo( $file );
            return [ $fileInfo[ 'filename' ] => $file ];
        });

        return $files[ $file ];
    }
}