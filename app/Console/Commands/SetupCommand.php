<?php

namespace App\Console\Commands;

use App\Services\Helper;
use App\Services\SetupService;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:setup {--store_name=} {--admin_username=} {--admin_email=} {--admin_password=} {--language=en}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install NexoPOS from the command line.';

    private $ns_store_name;

    private $admin_username;

    private $admin_email;

    private $admin_password;

    private $language;

    /**
     * determine if the actual command requis
     * a confirmation or can be skipped.
     */
    private $requireConfirmation = true;

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
        if (
            ! empty( $this->option( 'store_name' ) ) &&
            ! empty( $this->option( 'admin_email' ) ) &&
            ! empty( $this->option( 'admin_username' ) ) &&
            ! empty( $this->option( 'admin_password' ) )
        ) {
            $this->ns_store_name = $this->option( 'store_name' );
            $this->admin_email = $this->option( 'admin_email' );
            $this->admin_username = $this->option( 'admin_username' );
            $this->admin_password = $this->option( 'admin_password' );
            $this->language = $this->option( 'language' );
            $this->requireConfirmation = false;
        }

        if (
            env( 'DB_HOST', null ) === null ||
            env( 'DB_DATABASE', null ) === null ||
            env( 'DB_USERNAME', null ) === null ||
            env( 'DB_PASSWORD', null ) === null ||
            env( 'DB_PREFIX', null ) === null
        ) {
            return $this->error( __( 'Unable to proceed, looks like the database can\'t be used.' ) );
        }

        if ( Helper::installed() ) {
            return $this->error( __( 'NexoPOS is already installed.' ) );
        }

        $this->setupLanguage();
        $this->setupStoreName();
        $this->setupAdminUsername();
        $this->setupAdminPassword();
        $this->setupAdminEmail();

        $answer = 'n';
        if ( $this->requireConfirmation !== false ) {
            $answer = $this->ask( 'Everything seems ready. Would you like to proceed ? [Y]/[N]' );
        }

        if ( in_array( strtolower( $answer ), [ 'y', 'yes' ] ) || $this->requireConfirmation === false ) {
            /**
             * @var SetupService $service
             */
            $service = app()->make( SetupService::class );
            $service->runMigration( [
                'admin_username' => $this->admin_username,
                'admin_email' => $this->admin_email,
                'password' => $this->admin_password,
                'ns_store_name' => $this->ns_store_name,
            ] );

            return $this->info( 'Thank you, NexoPOS has been successfully installed.' );
        } else {
            return $this->info( 'The installation has been aborded.' );
        }
    }

    private function setupStoreName()
    {
        while ( empty( $this->ns_store_name ) ) {
            $this->ns_store_name = $this->ask( __( 'What is the store name ? [Q] to quit.' ) );

            if ( $this->ns_store_name === 'Q' ) {
                $this->info( 'the setup has been interrupted' );
                exit;
            }

            if ( strlen( $this->ns_store_name ) < 6 ) {
                $this->error( __( 'Please provide at least 6 characters for store name.' ) );
                $this->ns_store_name = null;
            }
        }
    }

    private function setupAdminPassword()
    {
        while ( empty( $this->admin_password ) ) {
            $this->admin_password = $this->secret( __( 'What is the administrator password ? [Q] to quit.' ) );

            if ( $this->admin_password === 'Q' ) {
                $this->info( 'the setup has been interrupted' );
                exit;
            }

            if ( strlen( $this->admin_password ) < 6 ) {
                $this->error( __( 'Please provide at least 6 characters for the administrator password.' ) );
                $this->admin_password = null;
            }
        }
    }

    private function setupLanguage()
    {
        while ( empty( $this->language ) ) {
            $langIndex = $this->choice( __( 'In which language would you like to install NexoPOS ?' ), array_values( config( 'nexopos.languages' ) ) );
            $this->language = array_keys( config( 'nexopos.languages' ) )[ $langIndex ];

            if ( strlen( $this->language ) != 2 ) {
                $this->error( __( 'You must define the language of installation.' ) );
                $this->language = null;
            }
        }
    }

    private function setupAdminEmail()
    {
        while ( empty( $this->admin_email ) ) {
            $this->admin_email = $this->ask( __( 'What is the administrator email ? [Q] to quit.' ) );

            if ( $this->admin_email === 'Q' ) {
                $this->info( 'the setup has been interrupted' );
                exit;
            }

            $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';

            if ( ! preg_match( $regex, $this->admin_email ) ) {
                $this->error( __( 'Please provide a valid email for the administrator.' ) );
                $this->admin_email = null;
            }
        }
    }

    private function setupAdminUsername()
    {
        while ( empty( $this->admin_username ) ) {
            $this->admin_username = $this->ask( __( 'What is the administrator username ? [Q] to quit.' ) );

            if ( $this->admin_username === 'Q' ) {
                $this->info( 'the setup has been interrupted' );
                exit;
            }

            if ( strlen( $this->admin_username ) < 5 ) {
                $this->error( __( 'Please provide at least 5 characters for the administrator username.' ) );
                $this->admin_username = null;
            }
        }
    }
}
