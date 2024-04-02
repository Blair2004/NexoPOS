<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
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

    protected $patterForClassWithoutComments = "/(use (?:\w|\W)*;\n*|namespace (?:\w|\W)*;\n)(\nclass)/";

    protected $typeMapping = [
        'bigint' => 'integer',
        'double' => 'float',
        'varchar' => 'string',
        'datetime' => '\Carbon\Carbon',
        'text' => 'string',
        'integer' => 'integer',
        'boolean' => 'bool',
        'float' => 'float',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return match ( $this->argument( 'argument' ) ) {
            'model-attributes' => $this->table( [ __( 'File Name' ), __( 'Status' )], $this->setModelAttributes() ),
            default => throw new Exception( sprintf( __( 'Unsupported argument provided: "%s"' ), $this->argument( 'argument' ) ) )
        };
    }

    protected function setModelAttributes()
    {
        $files = Storage::disk( 'ns' )->files( 'app/Models' );

        $bar = $this->output->createProgressBar( count( $files ) );

        $bar->start();

        $result = collect( $files )->map( function ( $file ) use ( $bar ) {
            $path = pathinfo( $file );
            $firstSlice = ucwords( str_replace( '/', '\\', $path[ 'dirname' ] ) );
            $className = $firstSlice . '\\' . $path[ 'filename' ];

            $bar->advance();

            if ( ! ( new ReflectionClass( $className ) )->isAbstract() ) {
                $model = new $className;
                $columns = Schema::getColumnListing( $model->getTable() );

                $withTypes = collect( $columns )->map( fn( $value ) => [ 'columnType' => Schema::getColumnType( $model->getTable(), $value ), 'columnName' => $value ] );
                $content = file_get_contents( base_path( $file ) );

                if ( $this->fileContentHasClassComments( $file, $content ) ) {
                    $preparedComments = $this->prepareComments( $withTypes );

                    $finalContent = $this->replacePreparedComments( $preparedComments, $content );

                    file_put_contents( base_path( $file ), $finalContent );

                    return [$file, __( 'Done' )];
                }
            }

            return false;
        } )->filter();

        $bar->finish();

        return $result;
    }

    protected function replacePreparedComments( $preparedComments, $content )
    {
        return preg_replace( $this->patterForClassWithoutComments, '$1' . $preparedComments . '$2', $content );
    }

    protected function prepareComments( Collection $withTypes )
    {
        $withTypes = $withTypes->map( function ( $column ) {
            return ' * @property ' . ( $this->typeMapping[ $column[ 'columnType'] ] ?? 'mixed' ) . ' $' . $column[ 'columnName' ];
        } );

        /**
         * only compatible files
         * are handled.
         */
        if ( $withTypes->count() > 0 ) {
            $withTypes->prepend( "\n/**" );
            $withTypes->push( '*/' );

            return $withTypes->join( "\n" );
        }

        return '';
    }

    protected function fileContentHasClassComments( $file, $content )
    {
        return preg_match_all( $this->patterForClassWithoutComments, $content, $matches );
    }
}
