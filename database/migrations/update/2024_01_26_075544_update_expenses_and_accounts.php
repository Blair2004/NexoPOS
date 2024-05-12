<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::rename( 'nexopos_expenses', 'nexopos_transactions' );
        }

        if ( Schema::hasTable( 'nexopos_expenses_categories' ) ) {
            Schema::rename( 'nexopos_expenses_categories', 'nexopos_transactions_accounts' );
        }

        if ( Schema::hasTable( 'nexopos_cash_flow' ) ) {
            Schema::rename( 'nexopos_cash_flow', 'nexopos_transactions_histories' );
        }

        include_once base_path() . '/database/permissions/transactions-accounts.php';

        $admin = Role::namespace( Role::ADMIN );
        $storeAdmin = Role::namespace( Role::STOREADMIN );

        $admin->addPermissions( Permission::includes( '.transactions-account' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.transactions-account' )->get()->map( fn( $permission ) => $permission->namespace ) );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
