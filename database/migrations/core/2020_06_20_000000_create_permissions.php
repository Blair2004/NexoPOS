<?php
/**
 * Table Migration
**/
use App\Models\Permission;
use App\Services\Options;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $options;

    protected $permission;

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
         * Categories Permissions.
         * let's create a constant which will allow the creation,
         * since these files are included as migration file
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        /**
         * All roles with basic permissions
         */
        // Crud for users and options
        foreach ( [ 'users', 'roles' ] as $permission ) {
            foreach ( [ 'create', 'read', 'update', 'delete' ] as $crud ) {
                // Create User
                $this->permission = Permission::firstOrNew( [ 'namespace' => $crud . '.' . $permission ] );
                $this->permission->name = ucwords( $crud ) . ' ' . ucwords( $permission );
                $this->permission->namespace = $crud . '.' . $permission;
                $this->permission->description = sprintf( __( 'Can %s %s' ), $crud, $permission );
                $this->permission->save();
            }
        }

        // for core update
        $this->permission = Permission::firstOrNew( [ 'namespace' => 'update.core' ] );
        $this->permission->name = __( 'Update Core' );
        $this->permission->namespace = 'update.core';
        $this->permission->description = __( 'Can update core' );
        $this->permission->save();

        // for core permission
        $this->permission = Permission::firstOrNew( [ 'namespace' => 'manage.profile' ] );
        $this->permission->name = __( 'Manage Profile' );
        $this->permission->namespace = 'manage.profile';
        $this->permission->description = __( 'Can manage profile' );
        $this->permission->save();

        // for module migration
        $this->permission = Permission::firstOrNew( [ 'namespace' => 'manage.modules' ] );
        $this->permission->name = __( 'Manage Modules' );
        $this->permission->namespace = 'manage.modules';
        $this->permission->description = __( 'Can manage module : install, delete, update, migrate, enable, disable' );
        $this->permission->save();

        // for options
        $this->permission = Permission::firstOrNew( [ 'namespace' => 'manage.options' ] );
        $this->permission->name = __( 'Manage Options' );
        $this->permission->namespace = 'manage.options';
        $this->permission->description = __( 'Can manage options : read, update' );
        $this->permission->save();

        // for options
        $this->permission = Permission::firstOrNew( [ 'namespace' => 'read.dashboard' ] );
        $this->permission->name = __( 'View Dashboard' );
        $this->permission->namespace = 'read.dashboard';
        $this->permission->description = __( 'Can access the dashboard and see metrics' );
        $this->permission->save();

        include_once dirname( __FILE__ ) . '/../../permissions/medias.php';
        include_once dirname( __FILE__ ) . '/../../permissions/categories.php';
        include_once dirname( __FILE__ ) . '/../../permissions/customers.php';
        include_once dirname( __FILE__ ) . '/../../permissions/customers-groups.php';
        include_once dirname( __FILE__ ) . '/../../permissions/coupons.php';
        include_once dirname( __FILE__ ) . '/../../permissions/transactions-accounts.php';
        include_once dirname( __FILE__ ) . '/../../permissions/transactions.php';
        include_once dirname( __FILE__ ) . '/../../permissions/orders.php';
        include_once dirname( __FILE__ ) . '/../../permissions/procurements.php';
        include_once dirname( __FILE__ ) . '/../../permissions/providers.php';
        include_once dirname( __FILE__ ) . '/../../permissions/products.php';
        include_once dirname( __FILE__ ) . '/../../permissions/registers.php';
        include_once dirname( __FILE__ ) . '/../../permissions/rewards.php';
        include_once dirname( __FILE__ ) . '/../../permissions/taxes.php';
        include_once dirname( __FILE__ ) . '/../../permissions/reports.php';
        include_once dirname( __FILE__ ) . '/../../permissions/payments-types.php';
        include_once dirname( __FILE__ ) . '/../../permissions/pos.php';
        include_once dirname( __FILE__ ) . '/../../permissions/widgets.php';
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where( 'namespace', 'like', '%nexopos.%' )->delete();
    }
};
