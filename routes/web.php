<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckMigrationStatus;
use App\Events\WebRoutesLoadedEvent;
use App\Http\Controllers\Dashboard\CrudController;
use App\Http\Controllers\Dashboard\ModulesController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\HandleCommonRoutesMiddleware;
use App\Http\Middleware\InstalledStateMiddleware;
use App\Http\Middleware\NotInstalledStateMiddleware;
use Illuminate\Support\Facades\Route;
use dekor\ArrayToTextTable;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Route as RoutingRoute;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome', [
        'title'     =>  __( 'Welcome &mdash; NexoPOS 4.x' )
    ]);
})->middleware([
    'web',
]);

include_once( dirname( __FILE__ ) . '/intermediate.php' );

Route::middleware([ 
    InstalledStateMiddleware::class, 
    CheckMigrationStatus::class, 
    SubstituteBindings::class 
])->group( function() {
    Route::get( '/sign-in', 'AuthController@signIn' )->name( 'ns.login' );
    Route::get( '/auth/activate/{user}/{token}', [ AuthController::class, 'activateAccount' ])->name( 'ns.activate-account' );
    Route::get( '/sign-up', 'AuthController@signUp' )->name( 'ns.register' );
    Route::get( '/password-lost', 'AuthController@passwordLost' );
    Route::get( '/new-password/{user}/{token}', [ AuthController::class, 'newPassword' ])->name( 'ns.new-password' );

    Route::post( '/auth/sign-in', 'AuthController@postSignIn' );
    Route::post( '/auth/sign-up', 'AuthController@postSignUp' )->name( 'ns.register.post' );
    Route::post( '/auth/password-lost', [ AuthController::class, 'postPasswordLost' ])->name( 'ns.password-lost' );
    Route::post( '/auth/new-password/{user}/{token}', [ AuthController::class, 'postNewPassword' ])->name( 'ns.post.new-password' );
    Route::get( '/sign-out', 'AuthController@signOut' )->name( 'ns.logout' );
    Route::get( '/database-update/', 'UpdateController@updateDatabase' )
        ->withoutMiddleware([ CheckMigrationStatus::class ])
        ->name( 'ns.database-update' );

    Route::middleware([ 
        Authenticate::class,
        CheckApplicationHealthMiddleware::class,
    ])->group( function() {
        Route::prefix( 'dashboard' )->group( function() {

            event( new WebRoutesLoadedEvent( 'dashboard' ) );

            Route::middleware([
                HandleCommonRoutesMiddleware::class
            ])->group( function() {
                require( dirname( __FILE__ ) . '/nexopos.php' );
            });

            Route::get( '/modules', [ ModulesController::class, 'listModules' ])->name( 'ns.dashboard.modules-list' );
            Route::get( '/modules/upload', [ ModulesController::class, 'showUploadModule' ])->name( 'ns.dashboard.modules-upload' );
            Route::get( '/modules/download/{identifier}', [ ModulesController::class, 'downloadModule' ])->name( 'ns.dashboard.modules-download' );
            Route::get( '/modules/migrate/{namespace}', [ ModulesController::class, 'migrateModule' ])->name( 'ns.dashboard.modules-migrate' );

            Route::get( '/users', [ UsersController::class, 'listUsers' ]);
            Route::get( '/users/create', [ UsersController::class, 'createUser' ]);
            Route::get( '/users/edit/{user}', [ UsersController::class, 'editUser' ])->name( 'ns.dashboard.users.edit' );
            Route::get( '/users/roles/permissions-manager', [ UsersController::class, 'permissionManager' ]);
            Route::get( '/users/profile', [ UsersController::class, 'getProfile' ])->name( 'ns.dashboard.users.profile' );
            Route::get( '/users/roles', [ UsersController::class, 'rolesList' ]);
            Route::get( '/users/roles/create', [ UsersController::class, 'createRole' ]);
            Route::get( '/users/roles/edit/{role}', [ UsersController::class, 'editRole' ]);

            Route::get( '/crud/download/{hash}', [ CrudController::class, 'downloadSavedFile' ])->name( 'ns.dashboard.crud-download' );
        });
    });
});

Route::middleware([ NotInstalledStateMiddleware::class ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'ns.do-setup' );
    });
});

if ( env( 'APP_DEBUG' ) ) {
    Route::get( '/routes', function() {
        $values     =   collect( array_values( ( array ) app( 'router' )->getRoutes() )[1] )->map( function( RoutingRoute $route ) {
            return [
                'uri'       =>  $route->uri(),
                'methods'   =>  collect( $route->methods() )->join( ', ' ),
                'name'      =>  $route->getName()
            ];
        })->values();
    
        return ( new ArrayToTextTable( $values->toArray() ) )->render();
    });
}
