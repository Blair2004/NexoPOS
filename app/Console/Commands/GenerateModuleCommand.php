<?php

namespace App\Console\Commands;

use App\Exceptions\NotAllowedException;
use App\Services\Helper;
use App\Services\ModulesService;
use Illuminate\Console\Command;

class GenerateModuleCommand extends Command
{
    /**
     * module description
     *
     * @var array
     */
    private $module = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
        {--force : Overwrite existing module}
        {--namespace= : Module namespace in PascalCase (e.g. FooBar)}
        {--name= : Human-readable module name (e.g. "Foo Bar Module")}
        {--author= : Author name}
        {--description= : Short module description}
        {--vers=1.0 : Module version (default: 1.0)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new NexoPOS module (supports --no-interaction for non-interactive/AI usage)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected ModulesService $moduleService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ( ! Helper::installed() ) {
            $this->error( 'NexoPOS is not yet installed.' );

            return 1;
        }

        /**
         * When running in non-interactive mode (--no-interaction / -n),
         * all required options must be provided via command-line flags.
         * This is useful for AI agents and CI/CD pipelines.
         */
        if ( ! $this->input->isInteractive() ) {
            return $this->handleNonInteractive();
        }

        $this->askInformations();

        return 0;
    }

    /**
     * Handle non-interactive module generation.
     * All required options must be provided as command-line flags.
     *
     * @return int Exit code (0 = success, 1 = error)
     */
    protected function handleNonInteractive(): int
    {
        $namespace = $this->option( 'namespace' );
        $name = $this->option( 'name' );
        $author = $this->option( 'author' );
        $description = $this->option( 'description' );

        $missing = [];

        if ( empty( $namespace ) ) {
            $missing[] = '--namespace';
        }
        if ( empty( $name ) ) {
            $missing[] = '--name';
        }
        if ( empty( $author ) ) {
            $missing[] = '--author';
        }
        if ( empty( $description ) ) {
            $missing[] = '--description';
        }

        if ( ! empty( $missing ) ) {
            $this->error( 'Missing required options for non-interactive mode: ' . implode( ', ', $missing ) );
            $this->line( '' );
            $this->line( 'Usage example:' );
            $this->line( '  php artisan make:module -n --namespace=FooBar --name="Foo Bar Module" --author="John Doe" --description="A sample module" [--vers=1.0] [--force]' );

            return 1;
        }

        $this->module = [
            'namespace' => ucwords( $namespace ),
            'name' => $name,
            'author' => $author,
            'description' => $description,
            'version' => $this->option( 'vers' ) ?: '1.0',
            'force' => $this->option( 'force' ),
        ];

        $this->info( 'Generating module with the following configuration:' );

        $table = [ 'Namespace', 'Name', 'Author', 'Description', 'Version' ];
        $this->table( $table, [ $this->module ] );

        try {
            $response = $this->moduleService->generateModule( $this->module );
            $this->info( $response[ 'message' ] );

            return 0;
        } catch ( NotAllowedException $exception ) {
            $this->error( 'A module with that namespace already exists. Use --force to overwrite.' );

            return 1;
        }
    }

    /**
     * ask for module information (interactive mode)
     *
     * @return void
     */
    public function askInformations()
    {
        $this->module[ 'namespace' ] = ucwords( $this->option( 'namespace' ) ?: $this->ask( 'Define the module namespace' ) );
        $this->module[ 'name' ] = $this->option( 'name' ) ?: $this->ask( 'Define the module name' );
        $this->module[ 'author' ] = $this->option( 'author' ) ?: $this->ask( 'Define the Author Name' );
        $this->module[ 'description' ] = $this->option( 'description' ) ?: $this->ask( 'Define a short description' );
        $this->module[ 'version' ] = $this->option( 'vers' ) ?: '1.0';
        $this->module[ 'force' ] = $this->option( 'force' );

        $table = [ 'Namespace', 'Name', 'Author', 'Description', 'Version' ];
        $this->table( $table, [ $this->module ] );

        if ( ! $this->confirm( 'Would you confirm theses informations' ) ) {
            $this->askInformations();
        }

        /**
         * let's try to create and if something
         * happens, we can still suggest the user to restart.
         */
        try {
            $response = $this->moduleService->generateModule( $this->module );
            $this->info( $response[ 'message' ] );
        } catch ( NotAllowedException $exception ) {
            $this->error( 'A similar module has been found' );

            if ( $this->confirm( 'Would you like to restart ?' ) ) {
                $this->askInformations();
            }
        }
    }
}
