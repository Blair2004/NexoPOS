<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;

class Module
{
    protected $module;

    protected $file;

    public function __construct( $file )
    {
        $this->file = $file;
    }

    /**
     * @deprecated
     */
    public static function namespace( $namespace )
    {
        /**
         * @var ModulesService
         */
        $modules = app()->make( ModulesService::class );
        $module = $modules->get( $namespace );

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
     *
     * @param  string $file
     * @return void
     *
     * @deprecated
     */
    public function loadFile( $file )
    {
        $filePath = Str::finish( $this->module[ 'path' ] . $file, '.php' );
        require $filePath;
    }
}
