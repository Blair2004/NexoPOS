<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateAddColumnsToExpenseHistoryAug1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_expenses_history' ) ) {
            Schema::table('nexopos_expenses_history', function (Blueprint $table) {
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'expense_category_id' ) ) {
                    $table->integer( 'expense_category_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'expense_id' ) ) {
                    $table->integer( 'expense_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'operation' ) ) {
                    $table->string( 'operation' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'procurement_id' ) ) {
                    $table->integer( 'procurement_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'order_refund_id' ) ) {
                    $table->integer( 'order_refund_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'order_id' ) ) {
                    $table->integer( 'order_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'register_history_id' ) ) {
                    $table->integer( 'register_history_id' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'customer_account_history_id' ) ) {
                    $table->integer( 'customer_account_history_id' )->nullable();
                }
    
                if ( Schema::hasColumn( 'nexopos_expenses_history', 'expense_name' ) ) {
                    $table->renameColumn( 'expense_name', 'name' );
                }
    
                if ( Schema::hasColumn( 'nexopos_expenses_history', 'expense_category_name' ) ) {
                    $table->dropColumn( 'expense_category_name' );
                }
            });
        }

        if ( ! Schema::hasTable( 'nexopos_cash_flow' ) ) {
            Schema::rename( 'nexopos_expenses_history', 'nexopos_cash_flow' );
        }

        /**
         * delete old permission
         */
        Permission::includes( '.expenses-history' )
            ->get()
            ->each( function( $permission ) {
                $permission->removeFromRoles();
                $permission->delete();
            });

        /**
         * create new permissions
         */
        $readCashFlowHistory                 =   Permission::withNamespaceOrNew( 'nexopos.read.cash-flow-history' );
        $readCashFlowHistory->name           =   __( 'Read Cash Flow History' );
        $readCashFlowHistory->namespace      =   'nexopos.read.cash-flow-history';
        $readCashFlowHistory->description    =   __( 'Allow to the Cash Flow History.' );
        $readCashFlowHistory->save();

        $deleteCashFlowHistory                 =   Permission::withNamespaceOrNew( 'nexopos.delete.cash-flow-history' );
        $deleteCashFlowHistory->name           =   __( 'Delete Cash Flow History' );
        $deleteCashFlowHistory->namespace      =   'nexopos.delete.cash-flow-history';
        $deleteCashFlowHistory->description    =   __( 'Allow to delete an Cash Flow History.' );
        $deleteCashFlowHistory->save();

        $readCashFlowHistory                 =   Permission::withNamespaceOrNew( 'nexopos.update.cash-flow-history' );
        $readCashFlowHistory->name           =   __( 'Update Cash Flow History' );
        $readCashFlowHistory->namespace      =   'nexopos.update.cash-flow-history';
        $readCashFlowHistory->description    =   __( 'Allow to the Cash Flow History.' );
        $readCashFlowHistory->save();

        $createCashFlowHistory                 =   Permission::withNamespaceOrNew( 'nexopos.create.cash-flow-history' );
        $createCashFlowHistory->name           =   __( 'Create Cash Flow History' );
        $createCashFlowHistory->namespace      =   'nexopos.create.cash-flow-history';
        $createCashFlowHistory->description    =   __( 'Allow to create a Cash Flow History.' );
        $createCashFlowHistory->save();

        /**
         * add new permissions
         */
        Role::namespace( 'admin' )
            ->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        Role::namespace( 'nexopos.store.administrator' )
            ->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_expenses_history' ) ) {
            Schema::table('nexopos_expenses_history', function (Blueprint $table) {
                if ( Schema::hasColumn( 'nexopos_expense_history', 'expense_category_id' ) ) {
                    $table->dropColumn( 'expense_category_id' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'expense_id' ) ) {
                    $table->dropColumn( 'expense_id' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'operation' ) ) {
                    $table->dropColumn( 'operation' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'expense_category_id' ) ) {
                    $table->dropColumn( 'expense_category_id' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'procurement_id' ) ) {
                    $table->dropColumn( 'procurement_id' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'order_id' ) ) {
                    $table->dropColumn( 'order_id' );
                }
                if ( Schema::hasColumn( 'nexopos_expense_history', 'register_history_id' ) ) {
                    $table->dropColumn( 'register_history_id' );
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_cash_flow' ) ) {
            Schema::rename( 'nexopos_cash_flow', 'nexopos_expense_history' );
        }
    }
}
