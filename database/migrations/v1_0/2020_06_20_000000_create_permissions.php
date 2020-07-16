<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\Options;

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
        
        include_once( dirname( __FILE__ ) . '/../../permissions/categories.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/coupons.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/expenses-categories.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/expenses.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/orders.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/procurements.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/products.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/registers.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/stores.php' );
        include_once( dirname( __FILE__ ) . '/../../permissions/taxes.php' );
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
