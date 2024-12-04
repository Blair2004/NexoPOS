<?php

namespace App\Services;

use App\Events\UserAfterActivationSuccessfulEvent;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetupService
{
    public Options $options;

    /**
     * Attempt database and save db informations
     *
     * @return void
     */
    public function saveDatabaseSettings( Request $request )
    {
        $databaseDriver = $request->input( 'database_driver' );

        config( [ 'database.connections.test' => [
            'driver' => $request->input( 'database_driver' ) ?: 'mysql',
            'host' => $request->input( 'hostname' ),
            'port' => $request->input( 'database_port' ) ?: env( 'DB_PORT', '3306' ),
            'database' => $request->input( 'database_driver' ) === 'sqlite' ? database_path( 'database.sqlite' ) : $request->input( 'database_name' ),
            'username' => $request->input( 'username' ),
            'password' => $request->input( 'password' ),
            'unix_socket' => env( 'DB_SOCKET', '' ),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => $request->input( 'database_prefix' ),
            'strict' => true,
            'engine' => null,
        ]] );

        try {
            DB::connection( 'test' )->getPdo();
        } catch ( \Exception $e ) {
            switch ( $e->getCode() ) {
                case 2002:
                    $message = [
                        'name' => 'hostname',
                        'message' => __( 'Unable to reach the host' ),
                        'status' => 'error',
                    ];
                    break;
                case 1045:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Unable to connect to the database using the credentials provided.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1049:
                    $message = [
                        'name' => 'database_name',
                        'message' => __( 'Unable to select the database.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1044:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Access denied for this user.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1698:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Incorrect Authentication Plugin Provided.' ),
                        'status' => 'error',
                    ];
                    break;
                default:
                    $message = [
                        'name' => 'hostname',
                        'message' => $e->getMessage(),
                        'status' => 'error',
                    ];
                    break;
            }

            return response()->json( $message, 403 );
        }

        // we'll empty the database
        file_put_contents( database_path( 'database.sqlite' ), '' );

        $this->updateAppUrl();
        $this->updateAppDBConfiguration( $request->post() );

        /**
         * Link the resource storage
         */
        Artisan::call( 'storage:link', [ '--force' => true ] );

        return [
            'status' => 'success',
            'message' => __( 'The connexion with the database was successful' ),
        ];
    }

    public function updateAppURL()
    {
        $domain = parse_url( url()->to( '/' ) );

        ns()->envEditor->set( 'APP_URL', url()->to( '/' ) );
        ns()->envEditor->set( 'SESSION_DOMAIN', $domain[ 'host' ] );
        ns()->envEditor->set( 'SANCTUM_STATEFUL_DOMAINS', $domain[ 'host' ] . ( isset( $domain[ 'port' ] ) ? ':' . $domain[ 'port' ] : '' ) );

        ns()->envEditor->set( 'REVERB_APP_ID', 'app-key-' . Str::random( 10 ) );
        ns()->envEditor->set( 'REVERB_APP_KEY', 'app-key-' . Str::random( 10 ) );
        ns()->envEditor->set( 'REVERB_APP_SECRET', Str::uuid() );
    }

    public function updateAppDBConfiguration( $data )
    {
        ns()->envEditor->set( 'DB_CONNECTION', $data[ 'database_driver' ] );

        if ( $data[ 'database_driver' ] === 'sqlite' ) {
            ns()->envEditor->set( 'DB_DATABASE', database_path( 'database.sqlite' ) );
            ns()->envEditor->set( 'DB_PREFIX', $data[   'database_prefix' ] );
        } elseif ( $data[ 'database_driver' ] === 'mysql' ) {
            ns()->envEditor->set( 'DB_HOST', $data[ 'hostname' ] );
            ns()->envEditor->set( 'DB_DATABASE', $data[ 'database_name' ] ?: database_path( 'database.sqlite' ) );
            ns()->envEditor->set( 'DB_USERNAME', $data[ 'username' ] );
            ns()->envEditor->set( 'DB_PASSWORD', $data[ 'password' ] );
            ns()->envEditor->set( 'DB_PREFIX', $data[   'database_prefix' ] );
            ns()->envEditor->set( 'DB_PORT', $data[ 'database_port' ] ?: 3306 );
        }
    }

    /**
     * Run migration
     *
     * @param Http Request
     * @return void
     */
    public function runMigration( $fields )
    {
        /**
         * We assume so far the application is installed
         * then we can launch option service
         */
        $configuredLanguage = $fields[ 'language' ] ?? 'en';

        App::setLocale( $configuredLanguage );

        /**
         * We're running this simple migration call to ensure
         * default tables are created. Those table are located at the
         * root of the database folder.
         */
        Artisan::call( 'migrate', [
            '--force' => true,
        ] );

        /**
         * NexoPOS uses Sanctum, we're making sure to publish the package.
         */
        Artisan::call( 'vendor:publish', [
            '--force' => true,
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
        ] );

        Artisan::call( 'ns:translate', [
            '--symlink' => true,
        ] );

        /**
         * we'll register all "update" migration
         * as already run as these migration are supposed
         * to be integrated on "create" files.
         */
        ns()->update
            ->getMigrations(
                directories: [ 'core', 'create' ],
                ignoreMigrations: true
            )
            ->each( function ( $file ) {
                ns()->update->executeMigrationFromFileName( $file );
            } );

        /**
         * The update migrations should'nt be executed.
         * This should improve the speed during the installation.
         */
        ns()->update
            ->getMigrations(
                directories: [ 'update' ],
                ignoreMigrations: true
            )
            ->each( function ( $file ) {
                ns()->update->assumeExecuted( $file );
            } );

        /**
         * clear all cache
         */
        Artisan::call( 'cache:clear' );

        /**
         * From this moment, new permissions has been created.
         * However Laravel gates aren't aware of them. We'll fix this here.
         */
        ns()->registerGatePermissions();

        $userID = rand( 1, 99 );
        $user = new User;
        $user->id = $userID;
        $user->username = $fields[ 'admin_username' ];
        $user->password = Hash::make( $fields[ 'password' ] );
        $user->email = $fields[ 'admin_email' ];
        $user->author = $userID;
        $user->active = true; // first user active by default;
        $user->save();
        $user->assignRole( 'admin' );

        /**
         * define default user language
         */
        $user->attribute()->create( [
            'language' => $fields[ 'language' ] ?? 'en',
        ] );

        UserAfterActivationSuccessfulEvent::dispatch( $user );

        $this->createDefaultPayment( $user );
        $this->createDefaultAccounting();

        $this->options = app()->make( Options::class );
        $this->options->setDefault();
        $this->options->set( 'ns_store_language', $configuredLanguage );

        return [
            'status' => 'success',
            'message' => __( 'NexoPOS has been successfully installed.' ),
        ];
    }

    public function createDefaultAccounting()
    {
        /**
         * @var TransactionService $service
         */
        $service = app()->make( TransactionService::class );
        $service->createDefaultAccounts();
    }

    public function createDefaultPayment( $user )
    {
        /**
         * let's create default payment
         * for the system
         */
        $cashPaymentType = new PaymentType;
        $cashPaymentType->label = __( 'Cash' );
        $cashPaymentType->identifier = 'cash-payment';
        $cashPaymentType->readonly = true;
        $cashPaymentType->author = $user->id;
        $cashPaymentType->save();

        $bankPaymentType = new PaymentType;
        $bankPaymentType->label = __( 'Bank Payment' );
        $bankPaymentType->identifier = 'bank-payment';
        $bankPaymentType->readonly = true;
        $bankPaymentType->author = $user->id;
        $bankPaymentType->save();

        $customerAccountType = new PaymentType;
        $customerAccountType->label = __( 'Customer Account' );
        $customerAccountType->identifier = 'account-payment';
        $customerAccountType->readonly = true;
        $customerAccountType->author = $user->id;
        $customerAccountType->save();
    }

    public function testDBConnexion()
    {
        try {
            $DB = DB::connection( env( 'DB_CONNECTION', 'mysql' ) )->getPdo();

            return [
                'status' => 'success',
                'message' => __( 'Database connection was successful.' ),
            ];
        } catch ( \Exception $e ) {
            switch ( $e->getCode() ) {
                case 2002:
                    $message = [
                        'name' => 'hostname',
                        'message' => __( 'Unable to reach the host' ),
                        'status' => 'error',
                    ];
                    break;
                case 1045:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Unable to connect to the database using the credentials provided.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1049:
                    $message = [
                        'name' => 'database_name',
                        'message' => __( 'Unable to select the database.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1044:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Access denied for this user.' ),
                        'status' => 'error',
                    ];
                    break;
                case 1698:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Incorrect Authentication Plugin Provided.' ),
                        'status' => 'error',
                    ];
                    break;
                default:
                    $message = [
                        'name' => 'hostname',
                        'message' => $e->getMessage(),
                        'status' => 'error',
                    ];
                    break;
            }

            return response()->json( $message, 403 );
        }
    }
}
