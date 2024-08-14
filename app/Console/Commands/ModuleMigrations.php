<?php

namespace App\Console\Commands;

use App\Models\ModuleMigration;
use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:migration {namespace} {--forget} {--forgetPath=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module migration';

    /**
     * @var array
     */
    protected $module;

    /**
     * @var string
     */
    protected $migration;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $schema;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->getModule();
    }

    /**
     * Get module
     *
     * @return void
     */
    public function getModule()
    {
        $modules = app()->make( ModulesService::class );
        $this->module = $modules->get( $this->argument( 'namespace' ) );

        if ( $this->module ) {
            if ( $this->passDeleteMigration() ) {
                $this->createMigration();
            }
        } else {
            $this->info( "Unable to locate the module \"{$this->argument( 'namespace' )}\"" );
        }
    }

    /**
     * Pass Delete Migration
     *
     * @return bool
     */
    public function passDeleteMigration()
    {
        if ( $this->option( 'forget' ) ) {
            /**
             * This will revert the migration
             * for a specific module.
             *
             * @var ModulesService
             */
            $moduleService = app()->make( ModulesService::class );
            $moduleService->revertMigrations( $this->module );

            /**
             * We'll make sure to clear the migration as
             * being executed on the system.
             */
            ModuleMigration::where( 'namespace', $this->module[ 'namespace' ] )->delete();
            $this->info( sprintf( 'The migration for the module %s has been forgotten.', $this->module[ 'name' ] ) );

            /**
             * because we use the cache to prevent the system for overusing the
             * database with too many requests.
             */
            Artisan::call( 'cache:clear' );

            return false;
        }

        if ( $this->option( 'forgetPath' ) ) {
            $path = str_replace( 'modules/', '', $this->option( 'forgetPath' ) );

            /**
             * This will revert the migration
             * for a specific module.
             *
             * @var ModulesService
             */
            $moduleService = app()->make( ModulesService::class );
            $moduleService->revertMigrations( $this->module, [ $path ] );

            /**
             * We'll make sure to clear the migration as
             * being executed on the system.
             */
            $migration = ModuleMigration::where( 'namespace', $this->module[ 'namespace' ] )
                ->where( 'file', $path )
                ->get();

            if ( $migration->count() > 0 ) {
                $migration->each( fn( $migration ) => $migration->delete() );

                $this->info( sprintf( 'The migration "%s" for the module %s has been forgotten.', $path, $this->module[ 'name' ] ) );

                /**
                 * because we use the cache to prevent the system for overusing the
                 * database with too many requests.
                 */
                Artisan::call( 'cache:clear' );

                return false;
            } else {
                $this->info( sprintf( 'No migration found using the provided file path "%s" for the module "%s".', $path, $this->module[ 'name' ] ) );

                return false;
            }
        }

        /**
         * If we'ven't deleted the migration then we can proceed from here
         */
        return true;
    }

    /**
     * Scream Content
     *
     * @return string content
     */
    public function streamContent( $content )
    {
        switch ( $content ) {
            case 'migration':
                return view( 'generate.modules.migration', [
                    'module' => $this->module,
                    'migration' => $this->migration,
                    'table' => $this->table,
                    'schema' => $this->schema,
                ] );
        }
    }

    /**
     * Create migration
     */
    public function createMigration()
    {
        $this->migration = $this->ask( 'Define the migration name. [Q] to finish, [T] for sample migration' );

        /**
         * Handle Exist
         */
        if ( $this->migration == 'Q' ) {
            return;
        } elseif ( $this->migration == 'T' ) {
            $this->migration = 'test table --table=test --schema=foo|bar|noob:integer';
        }

        /**
         * build right migration name by skipping arguments
         */
        $this->table = $this->__getTableName( $this->migration );
        $this->schema = $this->__getSchema( $this->migration );
        $this->migration = $this->__getMigrationName( $this->migration );

        $fileName = $this->module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . Str::studly( $this->migration ) . '.php';

        /**
         * Make sure the migration don't exist yet
         */
        if ( Storage::disk( 'ns-modules' )->exists( $fileName ) ) {
            return $this->info( 'A migration with the same name has been found !' );
        }

        /**
         * Create Migration file
         */
        Storage::disk( 'ns-modules' )->put(
            $fileName,
            $this->streamContent( 'migration' )
        );

        /**
         * Closing creating migration
         */
        $this->info( 'Migration Successfully created !' );

        /**
         * Asking another migration file name
         */
        $this->createMigration();
    }

    /**
     * Get table Name
     *
     * @param string
     * @return string
     */
    private function __getTableName( $migration )
    {
        $pieces = explode( ' ', $migration );
        $table = false;

        foreach ( $pieces as $piece ) {
            if ( substr( $piece, 0, 8 ) == '--table=' ) {
                $table = Str::snake( substr( $piece, 8 ) );
            }
        }

        return $table;
    }

    /**
     * Get schema for a specific migration line
     *
     * @param string migration
     * @return array of schema
     */
    private function __getSchema( string $migration )
    {
        $pieces = explode( ' ', $migration );
        $schema = [];

        foreach ( $pieces as $piece ) {
            if ( substr( $piece, 0, 9 ) == '--schema=' ) {
                $schema = $this->__parseSchema( Str::snake( substr( $piece, 9 ) ) );
            }
        }

        return $schema;
    }

    /**
     * Get table schema
     *
     * @param string
     * @return array
     */
    private function __parseSchema( string $schema )
    {
        $columns = explode( '|', $schema );
        $schemas = [];

        foreach ( $columns as $column ) {
            $details = explode( ':', $column );
            if ( count( $details ) == 1 ) {
                $schemas[ $details[0] ] = 'string';
            } else {
                $schemas[ $details[0] ] = $this->__checkColumnType( $details[1] );
            }
        }

        return $schemas;
    }

    /**
     * check column type
     *
     * @param string type
     * @return string type or default type
     */
    private function __checkColumnType( $type )
    {
        if ( ! in_array( $type, [
            'bigIncrements',
            'bigInteger', 'binary', 'boolean', 'char', 'date', 'datetime', 'decimal',
            'double', 'enum', 'float', 'increments', 'integer', 'json',
            'jsonb', 'longText', 'mediumInteger', 'mediumText',
            'morphs', 'nullableTimestamps', 'smallInteger', 'tinyInteger',
            'softDeletes', 'string', 'text', 'time',
            'timestamp', 'timestamps', 'rememberToken', 'unsigned',
        ] ) ) {
            return 'string';
        }

        return $type;
    }

    /**
     * get Migration Name
     *
     * @param string migration
     * @return string
     */
    private function __getMigrationName( $migration )
    {
        $name = '';
        $shouldIgnore = false;
        $details = explode( ' ', $migration );
        foreach ( $details as $detail ) {
            /**
             * while we've not looped the option, we assume the string
             * belong to the migration name
             */
            if ( substr( $detail, 0, 8 ) == '--table=' || substr( $detail, 0, 9 ) == '--schema=' ) {
                $shouldIgnore = true;
            }

            if ( ! $shouldIgnore ) {
                $name .= ' ' . ucwords( $detail );
            }
        }

        return $name;
    }
}
