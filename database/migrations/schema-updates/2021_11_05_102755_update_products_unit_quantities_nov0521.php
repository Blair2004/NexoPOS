<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateProductsUnitQuantitiesNov0521 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'low_quantity' ) ) {
                $table->float( 'low_quantity', 18, 5 )->default(0);
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'stock_alert_enabled' ) ) {
                $table->boolean( 'stock_alert_enabled' )->default(false);
            }
        });

        $permission                 =   Permission::where( 'namespace', 'nexopos.reports.low-stock' )->first();

        if ( ! $permission instanceof Permission ) {
            $permission                 =   new Permission();
        }

        $permission->name           =   __( 'Read Low Stock Report' );
        $permission->namespace      =   'nexopos.reports.low-stock';
        $permission->description    =   __( 'Let the user read the report that shows low stock.' );
        $permission->save();

        Role::namespace( Role::ADMIN )->addPermissions( $permission );
        Role::namespace( Role::STOREADMIN )->addPermissions( $permission );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'low_quantity' ) ) {
                $table->dropColumn( 'low_quantity' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'stock_alert_enabled' ) ) {
                $table->dropColumn( 'stock_alert_enabled' );
            }
        });
    }
}
