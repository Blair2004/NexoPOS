<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Dec8addNewPermissions extends Migration
{
    public $multistore     =   false;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $readHistory                 =   Permission::withNamespaceOrNew( 'nexopos.read.cash-flow-history' );
        $readHistory->name           =   __( 'Read Cash Flow History' );
        $readHistory->namespace      =   'nexopos.read.cash-flow-history';
        $readHistory->description    =   __( 'Allow to the Cash Flow History.' );
        $readHistory->save();

        $deleteHistory                 =   Permission::withNamespaceOrNew( 'nexopos.delete.cash-flow-history' );
        $deleteHistory->name           =   __( 'Delete Expense History' );
        $deleteHistory->namespace      =   'nexopos.delete.cash-flow-history';
        $deleteHistory->description    =   __( 'Allow to delete an expense history.' );
        $deleteHistory->save();

        Role::namespace( 'admin' )->addPermissions([
            $readHistory,
            $deleteHistory
        ]);

        Role::namespace( 'nexopos.store.administrator' )->addPermissions([
            $readHistory,
            $deleteHistory
        ]);
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
}
