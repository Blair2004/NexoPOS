<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:request {namespace} {name} {--force}';

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
     *
     * @return void
     */
    public function getModule()
    {
        $modules = app()->make( ModulesService::class );
        $this->module = $modules->get( $this->argument( 'namespace' ) );

        if ( $this->module ) {
            $this->createRequest();
        } else {
            $this->info( 'Unable to locate the module.' );
        }
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
                return view( 'generate.modules.request', [
                    'module' => $this->module,
                    'name' => $this->argument( 'name' ),
                ] );
        }
    }

    /**
     * Create migration
     */
    public function createRequest()
    {
        $requestName = Str::studly( $this->argument( 'name' ) );
        $fileName = $this->module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $requestName . '.php';

        /**
         * Make sure the migration don't exist yet
         */
        $fileExists = Storage::disk( 'ns-modules' )->exists(
            $fileName
        );

        if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
            return $this->info( sprintf(
                __( 'A request with the same name has been found !' ),
                $requestName
            ) );
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
