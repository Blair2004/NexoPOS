<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetSessionCookieCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:cookie {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform various operation on the session cookie.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        switch ( $this->argument( 'action' ) ) {
            case 'generate':
                ns()->envEditor->set( 'SESSION_COOKIE', strtolower( 'nexopos_' . Str::random( 5 ) ) );
                break;
        }
    }
}
