<?php

namespace App\Services;

use App\Events\UserAfterActivationSuccessfulEvent;
use App\Models\Migration;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Setup
{
    public Options $options;
    
    /**
     * Attempt database and save db informations
     *
     * @return void
     */
    public function saveDatabaseSettings( Request $request )
    {
        config([ 'database.connections.test' => [
            'driver' => $request->input( 'database_driver' ) ?: 'mysql',
            'host' => $request->input( 'hostname' ),
            'port' => $request->input( 'database_port' ) ?: env('DB_PORT', '3306'),
            'database' => $request->input( 'database_name' ) ?: database_path( 'database.sqlite' ),
            'username' => $request->input( 'username' ),
            'password' => $request->input( 'password' ),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => $request->input( 'database_prefix' ),
            'strict' => true,
            'engine' => null,
        ]]);

        try {
            $DB = DB::connection( 'test' )->getPdo();
        } catch (\Exception $e) {
            switch ( $e->getCode() ) {
                case 2002:
                    $message = [
                        'name' => 'hostname',
                        'message' => __( 'Unable to reach the host' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1045:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Unable to connect to the database using the credentials provided.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1049:
                    $message = [
                        'name' => 'database_name',
                        'message' => __( 'Unable to select the database.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1044:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Access denied for this user.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1698:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Incorrect Authentication Plugin Provided.' ),
                        'status' => 'failed',
                    ];
                    break;
                default:
                    $message = [
                        'name' => 'hostname',
                        'message' => $e->getMessage(),
                        'status' => 'failed',
                    ];
                    break;
            }

            return response()->json( $message, 403 );
        }

        ns()->envEditor->set( 'MAIL_MAILER', 'log' );
        ns()->envEditor->set( 'DB_HOST', $request->input( 'hostname' ) );
        ns()->envEditor->set( 'DB_DATABASE', $request->input( 'database_name' ) ?: database_path( 'database.sqlite' ) );
        ns()->envEditor->set( 'DB_USERNAME', $request->input( 'username' ) );
        ns()->envEditor->set( 'DB_PASSWORD', $request->input( 'password' ) );
        ns()->envEditor->set( 'DB_PREFIX', $request->input( 'database_prefix' ) );
        ns()->envEditor->set( 'DB_PORT', $request->input( 'database_port' ) ?: 3306 );
        ns()->envEditor->set( 'DB_CONNECTION', $request->input( 'database_driver' ) ?: 'mysql' );
        ns()->envEditor->set( 'APP_URL', url()->to( '/' ) );

        /**
         * Link the resource storage
         */
        Artisan::call( 'storage:link', [ '--force' => true ] );

        return [
            'status' => 'success',
            'message' => __( 'The connexion with the database was successful' ),
        ];
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
         * We're running this simple migration call to ensure
         * default tables are created. Those table are located at the 
         * root of the database folder.
         */
        Artisan::call( 'migrate' );

        /**
         * NexoPOS uses Sanctum, we're making sure to publish the package.
         */
        Artisan::call( 'vendor:publish', [
            '--force' => true,
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
        ]);

        Artisan::call( 'ns:translate', [
            '--symlink' => true,
        ]);

        $domain = pathinfo( url()->to( '/' ) );
        ns()->envEditor->set( 'NS_VERSION', config( 'nexopos.version' ) );
        ns()->envEditor->set( 'NS_AUTHORIZATION', Str::random(20) );
        ns()->envEditor->set( 'NS_SOCKET_PORT', 6001 );
        ns()->envEditor->set( 'NS_SOCKET_DOMAIN', $domain[ 'basename' ] );
        ns()->envEditor->set( 'NS_SOCKET_ENABLED', 'false' );
        ns()->envEditor->set( 'NS_ENV', 'production' );
        
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
            ->each( function( $file ) {
                ns()->update->executeMigrationFromFileName( $file );
            });

        /**
         * The update migrations should'nt be executed. 
         * This should improve the speed during the installation.
         */
        ns()->update
            ->getMigrations(
                directories: [ 'update' ],
                ignoreMigrations: true
            )
            ->each( function( $file ) {
                ns()->update->assumeExecuted( $file );
            });

        /**
         * From this moment, new permissions has been created.
         * However Laravel gates aren't aware of them. We'll fix this here.
         */
        ns()->registerGatePermissions();

        $userID = rand(1, 99);
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
        $user->attribute()->create([
            'language' => 'en',
        ]);

        UserAfterActivationSuccessfulEvent::dispatch( $user );
                
        $this->createDefaultPayment( $user );

        /**
         * We assume so far the application is installed
         * then we can launch option service
         */
        $this->options = app()->make( Options::class );
        $this->options->setDefault();

        return [
            'status' => 'success',
            'message' => __( 'NexoPOS has been successfully installed.' ),
        ];
    }

    public function createDefaultPayment( $user )
    {
        /**
         * let's create default payment
         * for the system
         */
        $paymentType = new PaymentType;
        $paymentType->label = __( 'Cash' );
        $paymentType->identifier = 'cash-payment';
        $paymentType->readonly = true;
        $paymentType->author = $user->id;
        $paymentType->save();

        $paymentType = new PaymentType;
        $paymentType->label = __( 'Bank Payment' );
        $paymentType->identifier = 'bank-payment';
        $paymentType->readonly = true;
        $paymentType->author = $user->id;
        $paymentType->save();

        $paymentType = new PaymentType;
        $paymentType->label = __( 'Customer Account' );
        $paymentType->identifier = 'account-payment';
        $paymentType->readonly = true;
        $paymentType->author = $user->id;
        $paymentType->save();
    }

    public function testDBConnexion()
    {
        try {
            $DB = DB::connection( env( 'DB_CONNECTION', 'mysql' ) )->getPdo();

            return [
                'status' => 'success',
                'message' => __( 'Database connection was successful.' ),
            ];
        } catch (\Exception $e) {
            switch ( $e->getCode() ) {
                case 2002:
                    $message = [
                        'name' => 'hostname',
                        'message' => __( 'Unable to reach the host' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1045:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Unable to connect to the database using the credentials provided.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1049:
                    $message = [
                        'name' => 'database_name',
                        'message' => __( 'Unable to select the database.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1044:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Access denied for this user.' ),
                        'status' => 'failed',
                    ];
                    break;
                case 1698:
                    $message = [
                        'name' => 'username',
                        'message' => __( 'Incorrect Authentication Plugin Provided.' ),
                        'status' => 'failed',
                    ];
                    break;
                default:
                    $message = [
                        'name' => 'hostname',
                        'message' => $e->getMessage(),
                        'status' => 'failed',
                    ];
                    break;
            }

            return response()->json( $message, 403 );
        }
    }
}
