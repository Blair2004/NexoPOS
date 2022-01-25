<?php

namespace App\Console\Commands;

use App\Services\DemoService;
use App\Models\Role;
use App\Services\ResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class ResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:reset {--mode=soft} {--user=default}';

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
     * @var DemoService $demoService
     */
    private $demoService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ResetService $resetService,
        DemoService $demoService
    )
    {
        parent::__construct();

        $this->resetService     =   $resetService;
        $this->demoService      =   $demoService;
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
            case 'grocery':
                $this->softReset();
                $this->initializeRole();
                $this->demoService->run([
                    'mode'                  =>  'grocery',
                    'create_sales'          =>  true,
                    'create_procurements'   =>  true,
                ]);
                $this->info( __( 'The demo has been enabled.' ) );
            break;
        }
    }

    private function initializeRole()
    {
        if ( $this->option( 'user' ) === 'default' ) {
            $user   =   Role::namespace( 'admin' )->users->first();
            Auth::loginUsingId( $user->id );
        } else {
            Auth::loginUsingId( $this->option( 'user' ) );
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
