<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:update {argument} {--module} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'An utility for updating NexoPOS and it\'s modules. Only works if the project was installed using Git.';

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
        if ( $this->option( 'module' ) ) {
            $this->proceedUpdateModule();
        } else {
            $this->proceedCoreUpdate();
        }
    }

    private function proceedUpdateModule()
    {
        // we need to update my.nexopos.com to support this.
    }

    /**
     * check the type of update
     * performed by the user.
     */
    private function proceedCoreUpdate()
    {
        switch ( $this->argument( 'argument' ) ) {
            case 'dev':
                $this->proceedDevPull();
                break;
            default:
                $this->proceedTagUpdate( $this->argument( 'argument' ) );
                break;
        }
    }

    private function proceedTagUpdate( $tag )
    {
        $gitpath = env( 'NS_GIT', 'git' );

        $this->info( __( 'Downloading latest dev build...' ) );

        if ( $this->option( 'force' ) ) {
            $this->info( __( 'Reset project to HEAD...' ) );
            $this->line( exec( "{$gitpath} reset HEAD --hard" ) );
        }

        $this->line( exec( "{$gitpath} pull" ) );
        $this->build();
    }

    private function build()
    {
        $composerpath = env( 'NS_COMPOSER', 'composer' );
        $npmpath = env( 'NS_NPM', 'npm' );

        $this->line( exec( "{$npmpath} i" ) );
        $this->line( exec( "{$composerpath} i" ) );
        $this->line( exec( "{$npmpath} run prod" ) );
    }

    /**
     * perform dev update and optionally
     * clear local changes.
     */
    private function proceedDevPull()
    {
        $gitpath = env( 'NS_GIT', 'git' );

        $this->info( __( 'Downloading latest dev build...' ) );

        if ( $this->option( 'force' ) ) {
            $this->info( __( 'Reset project to HEAD...' ) );
            $this->line( exec( "{$gitpath} reset HEAD --hard" ) );
        }

        $this->line( exec( "{$gitpath} pull" ) );
        $this->build();
    }
}
