<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAdjustRoleCreateNewPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
        $role           =   Role::namespace( 'nexopos.store.driver' );
        
        if ( $role instanceof Role ) {
            $permissions    =   Role::namespace( 'nexopos.store.driver' )
                ->permissions;

            $role->removePermissions( $permissions ); 
                
            $role->delete();
        }

        Schema::table( 'nexopos_roles', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_roles', 'dashid' ) ) {
                $table->string( 'dashid' )->nullable()->default( 'default' );
            }
        });

        /**
         * let's define the default dashboard
         * for the administrator
         */
        Role::whereIn( 'namespace', [
                'admin',
                'nexopos.store.administrator'
            ])
            ->get()
            ->each( function( $role ) {
                $role->dashid   =   'store';
                $role->save();
            });

        /**
         * let's define the dashboard for the
         * cashier
         */
        Role::whereIn( 'namespace', [
                'nexopos.store.cashier'
            ])
            ->get()
            ->each( function( $role ) {
                $role->dashid   =   'cashier';
                $role->save();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_roles', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_roles', 'dashid' ) ) {
                $table->removeColumn( 'dashid' );
            }
        });
    }
}
