<?php
namespace App\Services;
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
        Artisan::call( 'migrate:fresh --path=/database/migrations/v1_0' );
        
        /**
         * We assume so far the application is installed
         * then we can launch option service
         */
        $this->options  =   app()->make( Options::class );
        
        /**
         * Add permissions
         */
        $this->createPermissions();

        /**
         * Create Roles
         */
        $this->createRoles();
        
        $this->options->set( 'app_name', $request->input( 'app_name' ) );
        $this->options->set( 'allow_registration', false );
        $this->options->set( 'db_version', config( 'nexopos.db_version' ) );
        
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
         * Send Welcome email 
         * We're polit right here :)
         */
        // Mail::to( $user->email )->queue( 
        //     new MailSetupComplete()
        // );

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
        DotenvEditor::setKey( 'NS_VERSION', config( 'nexopos.version' ) );
        DotenvEditor::save();

        /**
         * Clear Cache
         */
        Artisan::call( 'cache:clear' );
        Artisan::call( 'config:clear' );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'Tendoo has been successfuly installed' )
        ];
    }

    /**
     * Create Permission
     * @param void
     * @return void
     */
    private function createPermissions()
    {
        /**
         * All roles with basic permissions
         */
        // Crud for users and options
        foreach( [ 'users', 'profile', 'applications' ] as $permission ) {
            foreach( [ 'create', 'read', 'update', 'delete' ] as $crud ) {
                // Create User
                $this->permission                   =   new Permission;
                $this->permission->name             =   ucwords( $crud ) . ' ' . ucwords( $permission );
                $this->permission->namespace        =   $crud . '.' . $permission;
                $this->permission->description      =   sprintf( __( 'Can %s %s' ), $crud, $permission );
                $this->permission->save();
            }
        }

        foreach( [ 'modules' ] as $permission ) {
            foreach( [ 'install', 'enable', 'disable', 'update', 'delete' ] as $crud ) {
                // Create User
                $this->permission                   =   new Permission;
                $this->permission->name             =   ucwords( $crud ) . ' ' . ucwords( $permission );
                $this->permission->namespace        =   $crud . '.' . $permission;
                $this->permission->description      =   sprintf( __( 'Can %s %s' ), $crud, $permission );
                $this->permission->save();
            }
        }

        // for core update
        $this->permission                   =   new Permission;
        $this->permission->name             =   __( 'Update Core' );
        $this->permission->namespace        =   'update.core';
        $this->permission->description      =   __( 'Can update core' );
        $this->permission->save();
        
        // for module migration
        $this->permission                   =   new Permission;
        $this->permission->name             =   __( 'Manage Modules' );
        $this->permission->namespace        =   'manage.modules';
        $this->permission->description      =   __( 'Can manage module : install, delete, update, migrate, enable, disable' );
        $this->permission->save();
        
        // for options
        $this->permission                   =   new Permission;
        $this->permission->name             =   __( 'Manage Options' );
        $this->permission->namespace        =   'manage.options';
        $this->permission->description      =   __( 'Can manage options : read, update' );
        $this->permission->save();
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

    /**
     * Create Roles
     * @param void
     * @return void
     */
    private function createRoles()
    {
        // User Role
        $this->role                 =   new Role;
        $this->role->name           =   __( 'User' );
        $this->role->namespace      =   'user';
        $this->role->locked         =   true;
        $this->role->description    =   __( 'Basic user role.' );
        $this->role->save();
        $this->role->addPermissions([ 
            'crud.profile' 
        ]); 

        // Admin Role
        $this->role                 =   new Role;
        $this->role->name           =   __( 'Supervisor' );
        $this->role->namespace      =   'supervisor';
        $this->role->locked         =   true;
        $this->role->description    =   __( 'Advanced role which can access to the dashboard manage settings.' );
        $this->role->save(); 
        $this->role->addPermissions([ 
            'crud.users', 
            'manage.options', 
            'crud.profile' 
        ]);

        // Master User
        $this->role                 =   new Role;
        $this->role->name           =   __( 'Administrator' );
        $this->role->namespace      =   'admin';
        $this->role->locked         =   true;
        $this->role->description    =   __( 'Master role which can perform all actions like create users, install/update/delete modules and much more.' );
        $this->role->save(); 
        $this->role->addPermissions([ 
            'crud.users', 
            'crud.profile', 
            'manage.options', 
            'manage.modules',
        ]);
    }
}