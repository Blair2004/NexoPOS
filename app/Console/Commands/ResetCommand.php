<?php

namespace App\Console\Commands;

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
    protected $signature = 'ns:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will wipe the database and force reinstallation. Cannot be undone.';

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
        Artisan::call( 'migrate:reset --path=/database/migrations/v1_0' );
        Artisan::call( 'migrate:reset --path=/database/migrations/v1_1' );
        Artisan::call( 'migrate:reset --path=/database/migrations/v1_2' );

        DotenvEditor::deleteKey( 'NS_VERSION' );
        DotenvEditor::deleteKey( 'SANCTUM_STATEFUL_DOMAINS' );
        DotenvEditor::deleteKey( 'SESSION_DOMAIN' );
        DotenvEditor::save();

        exec( 'rm -rf public/storage' );

        $this->info( 'The database has been cleared' );
    }
}
