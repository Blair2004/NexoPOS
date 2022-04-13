<?php

namespace App\Console\Commands;

use App\Services\DoctorService;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:doctor {--fix-roles} {--fix-users-attributes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will perform various tasks to fix issues on NexoPOS.';

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
        /**
         * @var DoctorService
         */
        $doctorService   =   app()->make( DoctorService::class );

        if( $this->option( 'fix-roles' ) ) {
            $doctorService->restoreRoles();
            return $this->info( 'The roles where correctly restored.' );
        }

        if ( $this->option( 'fix-users-attributes' ) ) {
            $doctorService->createUserAttribute();
            return $this->info( 'The users attributes were fixed.' );
        }
    }
}
