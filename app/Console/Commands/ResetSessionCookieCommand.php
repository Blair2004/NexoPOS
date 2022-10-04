<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

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
                DotenvEditor::load();
                DotenvEditor::setKey( 'SESSION_COOKIE', strtolower( 'nexopos_' . Str::random(5) ) );
                DotenvEditor::save();
                break;
        }
    }
}
