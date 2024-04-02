<?php

namespace App\Console\Commands;

use App\Http\Kernel as HttpKernel;
use App\Services\DateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateActivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:simulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a fake store activity';

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
        $date = app()->make( DateService::class );
        $rangeStarts = $date->copy()->subMonths( 5 );
        $totalDays = [];

        while ( ! $rangeStarts->isSameDay( $date ) ) {
            $totalDays[] = $rangeStarts->copy();
            $rangeStarts->addDay( 1 );
        }

        $bar = $this->output->createProgressBar( count( $totalDays ) );
        $bar->start();

        $files = Storage::disk( 'ns' )->allFiles( 'tests/Feature' );

        $app = require base_path( 'bootstrap/app.php' );
        $app->make( HttpKernel::class )->bootstrap();

        foreach ( $totalDays as $day ) {
            /**
             * include test files
             */
            foreach ( $files as $file ) {
                if ( ! in_array( $file, [
                    'tests/Feature/ResetTest.php',
                ] ) ) {
                    include_once base_path( $file );

                    $path = pathinfo( $file );
                    $class = collect( explode( '/', $path[ 'dirname' ] ) )
                        ->push( $path[ 'filename' ] )
                        ->map( fn( $dir ) => ucwords( $dir ) )
                        ->join( '\\' );

                    $object = new $class;
                    $methods = get_class_methods( $object );
                    $method = $methods[0];
                    $object->defineApp( $app );
                    $object->$method();
                }
            }

            $date->define( $day );
            $bar->advance();
        }

        $bar->finish();
    }
}
