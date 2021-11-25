<?php

namespace App\Console\Commands;

use App\Models\Migration;
use App\Models\ModuleMigration;
use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateForgetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:forget {module?} {--file=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will forget migration run for by a specific module to ensure the migration runs again.';

    /**
     * Module service
     * @var ModulesService
     */
    private $moduleService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ModulesService $moduleService
    )
    {
        parent::__construct();

        $this->moduleService    =   $moduleService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $module     =   $this->moduleService->get( $this->argument( 'module' ) );

        if ( $module === null ) {
            if ( ! in_array( $this->option( 'file' ), $module[ 'all-migrations' ] ) ) {
                return $this->error( sprintf( __( 'Unable to find the requested file "%s" from the module migration.'), $this->option( 'file' ) ) );
            }
    
            ModuleMigration::where( 'namespace', $this->argument( 'module' ) )
                ->where( 'file', $this->option( 'file' ) )
                ->delete();
            
            Artisan::call( 'cache:clear' );
    
        } else {

            Migration::where( 'migration', $this->option( 'file' ) )->delete();
            Artisan::call( 'cache:clear' );

        }

        return $this->info( __( 'The migration file has been successfully forgotten.' ) );
    }
}
