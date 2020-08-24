<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;

class Module
{
    public function __construct( $file )
    {
        $this->modules  =   app()->make( ModulesService::class );
        $this->module   =   $this->modules->asFile( $file );
        
        $eventFiles     =   Storage::disk( 'ns-modules' )->files( ucwords( $this->module[ 'namespace' ] ) . '\Events' );
        $fieldsFiles    =   Storage::disk( 'ns-modules' )->files( ucwords( $this->module[ 'namespace' ] ) . '\Fields' );
        
        // including events files
        foreach( $eventFiles as $file ) {
            // include_once( base_path() . CB_S . $file );
        }

        // including events files
        foreach( $fieldsFiles as $file ) {
            // include_once( base_path() . CB_S . $file );
        }
    }
}