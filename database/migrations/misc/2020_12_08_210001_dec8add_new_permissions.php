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
        $readHistory                 =   Permission::withNamespaceOrNew( 'nexopos.read.expenses-history' );
        $readHistory->name           =   __( 'Read Expense History' );
        $readHistory->namespace      =   'nexopos.read.expenses-history';
        $readHistory->description    =   __( 'Allow to the expense history.' );
        $readHistory->save();

        $deleteHistory                 =   Permission::withNamespaceOrNew( 'nexopos.delete.expenses-history' );
        $deleteHistory->name           =   __( 'Delete Expense History' );
        $deleteHistory->namespace      =   'nexopos.delete.expenses-history';
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
