<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Tendoo\Core\Models\Role;
use Tendoo\Core\Models\Permission;
use Tendoo\Core\Models\User;
use Tendoo\Core\Services\Options;

class CreatePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $this->options      =   app()->make( Options::class );
        
        /**
         * Categories Permissions.
         * let's create a constant which will allow the creation, 
         * since these files are included as migration file
         */
        define( 'NEXO_CREATE_PERMISSIONS', true );
        
        include_once( dirname( __FILE__ ) . '/../../Permissions/categories.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/coupons.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/expenses-categories.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/expenses.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/orders.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/procurements.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/products.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/registers.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/stores.php' );
        include_once( dirname( __FILE__ ) . '/../../Permissions/taxes.php' );
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Permission::where( 'namespace', 'like', '%nexopos.%' )->delete();
    }
}
