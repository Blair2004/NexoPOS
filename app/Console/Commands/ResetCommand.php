<?php

namespace App\Console\Commands;

use App\Services\ResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:reset {--mode=soft}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will wipe the database and force reinstallation. Cannot be undone.';

    /**
     * Reset service
     * @var ResetService $resetService
     */
    private $resetService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ResetService $resetService
    )
    {
        parent::__construct();

        $this->resetService     =   $resetService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {    
        switch( $this->option( 'mode' ) ) {
            case 'soft':
                return $this->softReset();
            break;
            case 'hard':
                return $this->hardReset();
            break;
        }
    }

    /**
     * Proceed hard reset
     * @return void 
     */
    private function hardReset()
    {
        $result     =   $this->resetService->hardReset();
        $this->info( $result[ 'message' ] );
    }

    /**
     * Proceed soft reset
     * @return void
     */
    private function softReset()
    {
        $result         =   $this->resetService->softReset();
        
        return $this->info( $result[ 'message' ] );
    }
}
