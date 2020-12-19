<?php

namespace App\Services;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use App\Mails\SetupComplete;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\Options;
use App\Services\UserOptions;

class Setup
{
    /**
     * Attempt database and save db informations
     * @return void
     */
    public function saveDatabaseSettings( Request $request )
    {
        config([ 'database.connections.test' => [
            'driver'         =>      'mysql',
            'host'           =>      $request->input( 'hostname' ),
            'port'           =>      env('DB_PORT', '3306'),
            'database'       =>      $request->input( 'database_name' ),
            'username'       =>      $request->input( 'username' ),
            'password'       =>      $request->input( 'password' ),
            'unix_socket'    =>      env('DB_SOCKET', ''),
            'charset'        =>      'utf8',
            'collation'      =>      'utf8_unicode_ci',
            'prefix'         =>      $request->input( 'database_prefix' ),
            'strict'         =>      true,
            'engine'         =>      null,
        ]]);

        try {
            $DB     =   DB::connection( 'test' )->getPdo();
        } catch (\Exception $e) {

            switch( $e->getCode() ) {
                case 2002   :   
                    $message =  [
                        'name'              =>   'hostname',
                        'message'           =>  __( 'Unable to reach the host' ),
                        'status'            =>  'failed'
                    ]; 
                break;
                case 1045   :   
                    $message =  [
                        'name'              =>   'username',
                        'message'           =>  __( 'Unable to connect to the database using the credentials provided.' ),
                        'status'            =>  'failed'
                    ];
                break;
                case 1049   :   
                    $message =  [
                         'name'             => 'database_name',
                         'message'          =>  __( 'Unable to select the database.' ),
                         'status'           =>  'failed'
                    ];
                break;
                case 1044   :   
                    $message =  [
                        'name'        => 'username',
                        'message'      =>  __( 'Access denied for this user.' ),
                        'status'       =>  'failed'
                    ];
                break;
                default     :   
                    $message =  [
                         'name'        => 'hostname',
                         'message'      =>  sprintf( __( 'Unexpected error occured. :%s' ), $e->getCode() ),
                         'status'       =>  'failed'
                    ]; 
                break;
            }

            return response()->json( $message, 403 );
        }

        DotEnvEditor::setKey( 'MAIL_MAILER', 'log' );
        DotEnvEditor::setKey( 'DB_HOST', $request->input( 'hostname' ) );
        DotEnvEditor::setKey( 'DB_DATABASE', $request->input( 'database_name' ) );
        DotEnvEditor::setKey( 'DB_USERNAME', $request->input( 'username' ) );
        DotEnvEditor::setKey( 'DB_PASSWORD', $request->input( 'password' ) );
        DotEnvEditor::setKey( 'DB_PREFIX', $request->input( 'database_prefix' ) );
        DotEnvEditor::setKey( 'DB_PORT', 3306 );
        DotEnvEditor::setKey( 'DB_CONNECTION', 'mysql' );
        DotEnvEditor::setKey( 'APP_URL', url()->to( '/' ) );
        DotenvEditor::save();

        /**
         * Link the resource storage
         */
        Artisan::call( 'storage:link' );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The connexion with the database was successful' )
        ];   
    }

    /**
     * Run migration
     * @param Http Request
     * @return void
     */
    public function runMigration( Request $request )
    {
        /**
         * Let's create the tables. The DB is supposed to be set
         */
        Artisan::call( 'config:cache' );
        Artisan::call( 'migrate --path=/database/migrations/default' );
        Artisan::call( 'migrate --path=/database/migrations/v1_0' );
        Artisan::call( 'vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"' );
        
        $userID             =   rand(1,99);
        $user               =   new User;
        $user->id           =   $userID;
        $user->username     =   $request->input( 'admin_username' );
        $user->password     =   bcrypt( $request->input( 'password' ) );
        $user->email        =   $request->input( 'admin_email' );
        $user->author       =   $userID;
        $user->active       =   true; // first user active by default;
        $user->save();
        
        /**
         * The main user is the master
         */
        User::set( $user )->as( 'admin' );

        /**
         * Login auth since we would like to create some basic options
         */
        Auth::loginUsingId( $user->id );

        /**
         * define option for the admin
         */
        $this->userOptions  =   app()->make( UserOptions::class );
        $this->userOptions->set( 'theme_class', 'dark-theme' ); 

        Auth::logout();
        
        /**
         * Set version to close setup
         */
        $domain     =   Str::replaceFirst( 'http://', '', url( '/' ) );
        $domain     =   Str::replaceFirst( 'https://', '', $domain );
        DotenvEditor::setKey( 'SANCTUM_STATEFUL_DOMAINS', $domain );
        DotenvEditor::setKey( 'NS_VERSION', config( 'nexopos.version' ) );
        DotenvEditor::save();

        /**
         * We assume so far the application is installed
         * then we can launch option service
         */
        $this->options      =   app()->make( Options::class );
        $this->options->set( 'ns_store_name', $request->input( 'ns_store_name' ) );
        $this->options->set( 'allow_registration', false );
        $this->options->set( 'db_version', config( 'nexopos.db_version' ) );

        /**
         * Clear Cache
         */
        Artisan::call( 'cache:clear' );
        Artisan::call( 'config:clear' );
        return [
            'status'    =>  'success',
            'message'   =>  __( 'Tendoo has been successfuly installed.' )
        ];
    }

    public function testDBConnexion()
    {
        try {
            $DB     =   DB::connection( 'mysql' )->getPdo();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'Database connexion was successful' )
            ];

        } catch (\Exception $e) {

            switch( $e->getCode() ) {
                case 2002   :   
                    $message =  [
                        'name'              =>   'hostname',
                        'message'           =>  __( 'Unable to reach the host' ),
                        'status'            =>  'failed'
                    ]; 
                break;
                case 1045   :   
                    $message =  [
                        'name'              =>   'username',
                        'message'           =>  __( 'Unable to connect to the database using the credentials provided.' ),
                        'status'            =>  'failed'
                    ];
                break;
                case 1049   :   
                    $message =  [
                         'name'             => 'database_name',
                         'message'          =>  __( 'Unable to select the database.' ),
                         'status'           =>  'failed'
                    ];
                break;
                case 1044   :   
                    $message =  [
                        'name'        => 'username',
                        'message'      =>  __( 'Access denied for this user.' ),
                        'status'       =>  'failed'
                    ];
                break;
                default     :   
                    $message =  [
                         'name'        => 'hostname',
                         'message'      =>  sprintf( __( 'Unexpected error occured. :%s' ), $e->getCode() ),
                         'status'       =>  'failed'
                    ]; 
                break;
            }

            return response()->json( $message, 403 );
        }
    }
}