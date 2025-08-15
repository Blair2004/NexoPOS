<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;

class MakeFieldsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:fields {class} {identifier} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Field class';

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     */
    public function __construct( Filesystem $files )
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $className = $this->argument( 'class' );
        $identifier = $this->argument( 'identifier' );
        $path = app_path( "Fields/{$className}.php" );
        $force = $this->option( 'force' );

        if ( $this->files->exists( $path ) && ! $force ) {
            $this->error( "The class {$className} already exists! Use --force to overwrite." );

            return 1;
        }

        $stub = $this->getStub();
        $stub = str_replace( ['DummyClass', 'DummyIdentifier'], [$className, $identifier], $stub );

        $relativePath = "app/Fields/{$className}.php";
        $this->files->put( $path, $stub );

        $this->info( "Field class {$className} created successfully at {$relativePath}." );

        return 0;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return View::file( resource_path( 'views/generate/field.blade.php' ) )->render();
    }
}
