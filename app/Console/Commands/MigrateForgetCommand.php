<?php

namespace App\Console\Commands;

use App\Models\Migration;
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
    protected $signature = 'migrate:forget {--file=} {--down}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will forget migration run for by a specific module to ensure the migration runs again.';

    /**
     * Module service
     *
     * @var ModulesServie
     */
    private $moduleService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ModulesService $moduleService
    ) {
        parent::__construct();

        $this->moduleService = $moduleService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fileOption = $this->option( 'file' );
        $downOption = $this->option( 'down' );

        if ( ! $fileOption ) {
            // List available migrations ordered by the most recent first
            $migrations = Migration::orderBy( 'id', 'desc' )->get();

            if ( $migrations->isEmpty() ) {
                return $this->info( __( 'No migrations found.' ) );
            }

            $choices = $migrations->pluck( 'migration' )->toArray();
            $selectedMigration = $this->choice( __( 'Select a migration to delete:' ), $choices );

            if ( $downOption ) {
                // Execute the "down" method of the migration
                $migrationFilePath = database_path( 'migrations/' . $selectedMigration . '.php' );

                if ( ! file_exists( $migrationFilePath ) ) {
                    return $this->error( __( 'Migration file not found: ' ) . $migrationFilePath );
                }

                require_once $migrationFilePath;

                $className = $this->getMigrationClassName( $selectedMigration );

                if ( ! class_exists( $className ) ) {
                    return $this->error( __( 'Migration class not found: ' ) . $className );
                }

                $migrationInstance = new $className;

                if ( ! method_exists( $migrationInstance, 'down' ) ) {
                    return $this->error( __( 'The "down" method does not exist in the migration class.' ) );
                }

                $migrationInstance->down();
                $this->info( __( 'The "down" method has been executed.' ) );
            }

            // Delete the selected migration
            Migration::where( 'migration', $selectedMigration )->delete();
            Artisan::call( 'cache:clear' );

            return $this->info( __( 'Migration deleted: ' ) . $selectedMigration );
        }

        // Handle the case where --file option is provided
        $deleted = Migration::where( 'migration', $fileOption )->delete();
        Artisan::call( 'cache:clear' );

        return $this->info(
            sprintf(
                __( '%s migration(s) has been deleted.' ),
                $deleted
            )
        );
    }

    /**
     * Get the class name of a migration from its file name.
     */
    private function getMigrationClassName( string $fileName ): string
    {
        return collect( explode( '_', $fileName ) )
            ->map( fn( $part ) => ucfirst( $part ) )
            ->implode( '' );
    }
}
