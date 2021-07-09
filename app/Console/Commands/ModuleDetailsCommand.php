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
    protected $signature = 'ns:module {identifier}';

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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $moduleService  =   app()->make( ModulesService::class );
        $module         =   $moduleService->get( $this->argument( 'identifier' ) );

        $entries        =   [
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
            [ __( 'Dashboard' ), $module[ 'dashboard-path' ] ],
        ];

        return $this->table([
            __( 'Attribute' ),
            __( 'Value' )
        ], $entries );
    }
}
