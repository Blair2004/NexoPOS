<?php

namespace App\Console\Commands;

use App\Services\WebSocketService;
use Illuminate\Console\Command;

class NsWebSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:websocket {--reset=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allow to customize web sockets.';

    private $webSocketService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WebSocketService $webSocketService
    )
    {
        parent::__construct();

        $this->webSocketService     =   $webSocketService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->option( 'reset' ) === null ) {
            $this->webSocketService->generateFakeCredentials();
            return $this->info( 'The websocket credentials has been reinitialized.' );
        }
    }
}
