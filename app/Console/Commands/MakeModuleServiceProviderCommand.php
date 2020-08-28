<?php

namespace App\Console\Commands;

use App\Services\Helper;
use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MakeModuleServiceProviderCommand extends Command
{
    /**
     * module description
     * @var array
     */
    private $module     =   [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:provider {namespace} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module service provider';

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
        if ( ns()->installed() ) {
            if ( ! empty( $this->argument( 'namespace' ) && ! empty( $this->argument( 'name' ) ) ) ) {
                
                $modules     =   app()->make( ModulesService::class );

                /**
                 * Check if the module exists
                 */
                if ( $module = $modules->get( $this->argument( 'namespace') ) ) {
                    $fileName   =   ucwords( Str::camel( $this->argument( 'name' ) ) );
                    $filePath   =   $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $fileName . '.php';
                    if ( ! Storage::disk( 'ns-modules' )->exists( $filePath ) ) {
                        Storage::disk( 'ns-modules' )->put( 
                            $filePath,
                            view( 'generate.modules.providers', [
                                'module'    =>  $module,
                                'className' =>  $fileName
                            ])
                        );
                        return $this->info( 'The service provider "' . $fileName . '" has been created for "' . $module[ 'name' ] . '"' );
                    }
                    return $this->info( 'A service provider with the same file name already exists.' );
                } else {
                    $this->info( 'Unable to find that module.' );
                }
            }
        } else {
            $this->info( 'NexoPOS is not yet installed.' );
        }
    }
}


