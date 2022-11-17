<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

class DevShortCutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:dev {argument}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform various shortcut commands';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return match( $this->argument( 'argument' ) ) {
            'model-attributes'  =>  $this->setModelAttributes()
        };
    }

    protected function setModelAttributes()
    {
        $files  =   Storage::disk( 'ns' )->files( 'app/Models' );

        collect( $files )->each( function( $file ) {
            $path   =   pathinfo( $file );
            $firstSlice     =   ucwords( str_replace( '/', '\\', $path[ 'dirname' ] ) );
            $className  =   $firstSlice . '\\' . $path[ 'filename' ];

            if ( ! ( new ReflectionClass( $className ) )->isAbstract() ) {
                $model      =   new $className;
                $columns    =   Schema::getColumnListing( $model->getTable() );
    
                $withTypes  =   collect( $columns )->mapWithKeys( fn( $value ) => [ Schema::getColumnType( $model->getTable(), $value ) => $value ]);
                
                $this->fileContentHasClassComments( $file );


            }
        });
    }

    protected function fileContentHasClassComments( $file )
    {
        // $pattern =   "/\/\*(?:\*| )*\n(?: |\W|\w)*\n *\*\/\nclass *\w*/";
        $pattern =   "/(use (?:\w|\W)*;\n*|namespace (?:\w|\W)*;\n)(\nclass)/";
        $content =   file_get_contents( base_path( $file ) );
        $matches =   [];

        if( preg_match_all( $pattern, $content, $matches ) ) {
            dump( $file, $matches );
        }
    }
}
