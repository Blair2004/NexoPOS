<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class GenerateAuthorizationToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:authorization';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new authorization token.';

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
        DotenvEditor::load();
        DotenvEditor::setKey( 'NS_AUTHORIZATION', Str::random(20) );
        DotEnvEditor::save();
        $this->info( 'The authorization token has been refreshed.' );
    }
}
