<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class ModuleKernel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:kernel {module} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate kernel file for the selected module.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $moduleService = app()->make( ModulesService::class );
        $moduleIdentifier = $this->argument( 'module' );

        $modulePath = base_path( 'modules/' . $moduleIdentifier );

        if ( ! $module = $moduleService->get( $moduleIdentifier ) ) {
            $this->error( 'Module not found.' );
        }

        $filePath = $modulePath . '/Console/Kernel.php';

        if ( file_exists( $filePath ) && ! $this->option( 'force' ) ) {
            return $this->error( 'Kernel file not found for the module.' );
        }

        $content = View::make( 'generate.modules.kernel', compact( 'module' ) )->render();

        file_put_contents( $filePath, $content );

        $this->info( sprintf( 'Kernel file generated successfully at %s.', $filePath ) );
    }
}
