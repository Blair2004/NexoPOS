<?php

namespace App\Console\Commands;

use App\Models\ModuleMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Modules;
use App\Services\Setup;
use App\Services\Helper;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Artisan;

class ModuleRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:request {namespace} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module request';

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
     * @return void
     */
    public function getModule()
    {
        $modules   =   app()->make( ModulesService::class );
        $this->module   =   $modules->get( $this->argument( 'namespace' ) );

        if ( $this->module ) {
            $this->createRequest();
        } else {
            $this->info( 'Unable to locate the module.' );
        }
    }

    /**
     * Scream Content
     * @return string content
     */
    public function streamContent( $content ) 
    {
        switch ( $content ) {
            case 'migration'     :   
            return view( 'generate.modules.request', [
                'module'    =>  $this->module,
                'name'      =>  $this->argument( 'name' )
            ]); 
        }
    }

    /**
     * Create migration
     */
    public function createRequest()
    {
        $fileName           =   $this->module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . Str::studly( $this->argument( 'name' ) ) . '.php';

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
        $this->info( 'The request has been successfully created !' );
    }
}
