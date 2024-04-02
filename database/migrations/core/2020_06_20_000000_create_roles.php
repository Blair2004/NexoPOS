<?php
/**
 * Table Migration
**/
use App\Models\Role;
use App\Services\Options;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->options = app()->make( Options::class );

        /**
         * Each of the following files will define a role
         * and permissions that are assigned to those roles.
         */
        include_once dirname( __FILE__ ) . '/../../permissions/user-role.php';
        include_once dirname( __FILE__ ) . '/../../permissions/admin-role.php';
        include_once dirname( __FILE__ ) . '/../../permissions/store-admin-role.php';
        include_once dirname( __FILE__ ) . '/../../permissions/store-cashier-role.php';
        include_once dirname( __FILE__ ) . '/../../permissions/store-customer-role.php';
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::where( 'namespace', 'nexopos.store.administrator' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

        $role = Role::where( 'namespace', 'nexopos.store.cashier' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

        $role = Role::where( 'namespace', Role::STORECUSTOMER )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }
    }
};
