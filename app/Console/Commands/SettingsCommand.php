<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:settings {fileName} {--module=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate settings class for core or defined module.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $fileName = $this->argument( 'fileName' );

        /**
         * we should provide a valid file name,
         * or we throw an error.
         */
        preg_match( '/^[\w]+$/', $fileName, $regSearch );

        if ( empty( $regSearch ) ) {
            $this->error( __( 'You\'ve not provided a valid file name. It shouldn\'t contains any space, dot or special characters.' ) );
            exit;
        }

        if ( ! empty( $this->option( 'module' ) ) ) {

            $moduleNamespace = $this->option( 'module' );

            /**
             * @var ModulesService $moduleService
             */
            $moduleService = app()->make( ModulesService::class );

            $module = $moduleService->get( $moduleNamespace );

            if ( $module ) {
                $filePath = 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Forms' . DIRECTORY_SEPARATOR . $fileName . '.php';

                $this->putFile(
                    filePath: $filePath,
                    data: [
                        'className' => $fileName,
                        'module' => $module,
                    ]
                );
            } else {
                $this->error( sprintf( __( 'Unable to find a module having "%s" as namespace.' ), $moduleNamespace ) );
                exit;
            }
        } else {
            $filePath = 'app' . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR . $fileName . '.php';

            $this->putFile(
                filePath: $filePath,
                data: [
                    'className' => $fileName,
                ]
            );

            exit;
        }
    }

    private function putFile( $filePath, $data )
    {
        if ( file_exists( base_path( $filePath ) ) && ! $this->option( 'force' ) ) {
            $this->error( sprintf(
                __( 'A similar file already exists at the path "%s". Use "--force" to overwrite it.' ),
                $filePath
            ) );
            exit;
        }

        Storage::disk( 'ns' )->put(
            $filePath,
            view( 'generate.form', $data )
        );

        $this->info( sprintf(
            __( 'A new form class was created at the path "%s"' ),
            $filePath
        ) );
    }
}
