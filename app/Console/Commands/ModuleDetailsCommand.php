<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;

class ModuleDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:list {identifier?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show module details';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private ModulesService $modulesService
    ) {
        parent::__construct();
    }

    public function listAllModules()
    {
        $header = [
            __( 'Name' ),
            __( 'Namespace' ),
            __( 'Version' ),
            __( 'Author' ),
            __( 'Enabled' ),
        ];

        $modulesList = $this->modulesService->get();
        $modulesTable = [];

        foreach ( $modulesList as $module ) {
            $modulesTable[] = [
                $module[ 'name' ],
                $module[ 'namespace' ],
                $module[ 'version' ],
                $module[ 'author' ],
                $module[ 'enabled' ] ? __( 'Yes' ) : __( 'No' ),
            ];
        }

        $this->table( $header, $modulesTable );
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( empty( $this->argument( 'identifier' ) ) ) {
            $this->listAllModules();
        } else {
            $this->listSingleModule();
        }
    }

    private function listSingleModule()
    {
        /**
         * @var ModulesService
         */
        $moduleService = app()->make( ModulesService::class );

        $module = $moduleService->get( $this->argument( 'identifier' ) );

        if ( empty( $module ) ) {
            $this->error( __( 'Unable to find the requested module.' ) );
        }

        $entries = [
            [ __( 'Name' ), $module[ 'name' ] ],
            [ __( 'Version' ), $module[ 'version' ] ],
            [ __( 'Enabled' ), $module[ 'enabled' ] ? __( 'Yes' ) : __( 'No' ) ],
            [ __( 'Path' ), $module[ 'path' ] ],
            [ __( 'Index' ), $module[ 'index-file' ] ],
            [ __( 'Entry Class' ), $module[ 'entry-class' ] ],
            [ __( 'Routes' ), $module[ 'routes-file' ] ],
            [ __( 'Api' ), $module[ 'api-file' ] ],
            [ __( 'Controllers' ), $module[ 'controllers-relativePath' ] ],
            [ __( 'Views' ), $module[ 'views-path' ] ],
            [ __( 'Api File' ), $module[ 'api-file' ] ],
            [ __( 'Migrations' ), collect( $module[ 'all-migrations' ] ?? [] )->join( "\n" ) ],
        ];

        return $this->table( [
            __( 'Attribute' ),
            __( 'Value' ),
        ], $entries );
    }
}
