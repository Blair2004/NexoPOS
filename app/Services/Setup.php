<?php

namespace App\Services;

use App\Models\Migration;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

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

        DotEnvEditor::load();
        DotEnvEditor::setKey( 'MAIL_MAILER', 'log' );
        DotEnvEditor::setKey( 'DB_HOST', $request->input( 'hostname' ) );
        DotEnvEditor::setKey( 'DB_DATABASE', $request->input( 'database_name' ) ?: database_path( 'database.sqlite' ) );
        DotEnvEditor::setKey( 'DB_USERNAME', $request->input( 'username' ) );
        DotEnvEditor::setKey( 'DB_PASSWORD', $request->input( 'password' ) );
        DotEnvEditor::setKey( 'DB_PREFIX', $request->input( 'database_prefix' ) );
        DotEnvEditor::setKey( 'DB_PORT', $request->input( 'database_port' ) ?: 3306 );
        DotEnvEditor::setKey( 'DB_CONNECTION', $request->input( 'database_driver' ) ?: 'mysql' );
        DotEnvEditor::setKey( 'APP_URL', url()->to( '/' ) );
        DotenvEditor::save();

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
        Artisan::call( 'migrate', [
            '--force' => true,
            '--path' => 'database/migrations/2022_10_28_123458_setup_migration_table.php',
        ]);

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
            ->each( function ( $file ) {
                ns()->update->assumeExecuted( $file );
            });

        $userID = rand(1, 99);

        $user = new User;
        $user->id = $userID;
        $user->username = $fields[ 'admin_username' ];
        $user->password = Hash::make( $fields[ 'password' ] );
        $user->email = $fields[ 'admin_email' ];
        $user->author = $userID;
        $user->active = true; // first user active by default;
        $user->save();

        /**
         * The main user is the master
         */
        User::set( $user )->as( 'admin' );

        /**
         * define default user language
         */
        $user->attribute()->create([
            'language' => 'en',
        ]);

        $this->createDefaultPayment( $user );

        $domain = pathinfo( url()->to( '/' ) );

        DotenvEditor::load();
        DotenvEditor::setKey( 'NS_VERSION', config( 'nexopos.version' ) );
        DotenvEditor::setKey( 'NS_AUTHORIZATION', Str::random(20) );
        DotenvEditor::setKey( 'NS_SOCKET_PORT', 6001 );
        DotenvEditor::setKey( 'NS_SOCKET_DOMAIN', $domain[ 'basename' ] );
        DotenvEditor::setKey( 'NS_SOCKET_ENABLED', 'false' );
        DotenvEditor::setKey( 'NS_ENV', 'production' );
        DotenvEditor::save();

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
