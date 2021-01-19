<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class Module
{
    protected $module;

    public function __construct( $file )
    {
        $this->modules  =   app()->make( ModulesService::class );

        if ( is_array( $file ) ) {
            $this->module   =   $file;
        } else {
            $this->module   =   $this->modules->asFile( $file );
        }
    }

    public static function namespace( $namespace )
    {
        /**
         * @var ModulesService
         */
        $modules        =   app()->make( ModulesService::class );
        $module         =   $modules->get( $namespace );

        /**
         * when there is a match 
         * for the requested module
         */
        if ( $module ) {
            return new Module( $module );
        }

        throw new Exception( __( 'Unable to locate the requested module.' ) );
    }

    /**
     * Include specific module file
     * @param string $file
     * @return void
     */
    public function loadFile( $file )
    {
        require( Str::finish( $this->module[ 'path' ] . $file, '.php' ) );
    }
}