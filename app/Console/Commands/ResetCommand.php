<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\DemoService;
use App\Services\ResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class ResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:reset {--mode=soft} {--user=default} {--with-sales} {--with-procurements}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will wipe the database and force reinstallation. Cannot be undone.';

    /**
     * Reset service
     */
    private ResetService $resetService;

    /**
     * Demo service
     */
    private DemoService $demoService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ResetService $resetService,
        DemoService $demoService
    ) {
        parent::__construct();

        $this->resetService = $resetService;
        $this->demoService = $demoService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        switch ( $this->option( 'mode' ) ) {
            case 'soft':
                return $this->softReset();
            case 'hard':
                return $this->hardReset();
            case 'wipe_plus_grocery':
                $this->softReset();
                $this->initializeRole();
                $this->demoService->run( [
                    'mode' => $this->option( 'mode' ),
                    'create_sales' => $this->option( 'with-sales' ) && $this->option( 'with-procurements' ) ? true : false,
                    'create_procurements' => $this->option( 'with-procurements' ) ? true : false,
                ] );
            return $this->info( __( 'The demo has been enabled.' ) );
        }

        /**
         * We'll handle custom reset in case no condition is matched above,
         * but we want to run some custom logic.
         */
        $response = $this->resetService->handleCustom( [
            'mode' => $this->option( 'mode' ),
            'create_sales' => $this->option( 'with-sales' ) && $this->option( 'with-procurements' ) ? true : false,
            'create_procurements' => $this->option( 'with-procurements' ) ? true : false,
        ] );

        if ( $response && is_array( $response ) && isset( $response[ 'status' ] ) && isset( $response[ 'message' ] ) ) {
            if ( $response[ 'status' ] === 'success' ) {
                return $this->info( $response[ 'message' ] );
            } else {
                return $this->error( $response[ 'message' ] );
            }
        } else {
            return $this->error( __( 'An unexpected error occurred while handling the custom reset.' ) );
        }
    }

    private function initializeRole()
    {
        if ( $this->option( 'user' ) === 'default' ) {
            $user = Role::namespace( 'admin' )->users->first();
            Auth::loginUsingId( $user->id );
        } else {
            Auth::loginUsingId( $this->option( 'user' ) );
        }
    }

    /**
     * Proceed hard reset
     */
    private function hardReset(): void
    {
        $result = $this->resetService->hardReset();

        $this->info( $result[ 'message' ] );
    }

    /**
     * Proceed soft reset
     */
    private function softReset(): void
    {
        $result = $this->resetService->softReset();

        $this->info( $result[ 'message' ] );
    }
}
