<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DotEnvSetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:set {key} {--v=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set an environment value on the .env file';

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
        if ( in_array( strtoupper( $this->argument( 'key' ) ), [ 'NS_AUTHORIZATION' ] ) ) {
            return $this->error( __( 'The authorization token can\'t be changed manually.' ) );
        }

        ns()->envEditor->set( strtoupper( $this->argument( 'key' ) ), $this->option( 'v' ) );

        $this->info( 'The environment value has been set.' );
    }
}
