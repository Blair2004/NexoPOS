<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\WidgetService;
use App\Widgets\ProfileWidget;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_roles', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_roles', 'dashid' ) ) {
                $table->removeColumn( 'dashid' );
            }
        });

        /**
         * let's create a constant which will allow the creation,
         * since these files are included as migration file
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        /**
         * let's include the files that will create permissions
         * for all the declared widgets.
         */
        include( dirname( __FILE__ ) . '/../../permissions/widgets.php' );

        /**
         * We'll now defined default permissions
         */
        $admin          =   Role::namespace( Role::ADMIN );
        $storeAdmin     =   Role::namespace( Role::STOREADMIN );
        $storeCashier   =   Role::namespace( Role::STORECASHIER );
        
        $admin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeCashier->addPermissions( Permission::whereIn( 'namespace', [
            ( new ProfileWidget )->getPermission()
        ])->get()->map( fn( $permission ) => $permission->namespace ) );


        /**
         * We're introducing a driver role
         */
        include_once( dirname( __FILE__ ) . '/../../permissions/store-driver-role.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/store-customer-role.php' );

        /**
         * to all roles available, we'll make all available widget added
         * to their dashboard
         * @var WidgetService $widgetService
         */
        $widgetService  =   app()->make( WidgetService::class );
        
        User::get()->each( fn( $user ) => $widgetService->addDefaultWidgetsToAreas( $user ) );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
