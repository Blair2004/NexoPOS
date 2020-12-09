<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Dec8addNewPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $readHistory                 =   new Permission;
        $readHistory->name           =   __( 'Read Expense History' );
        $readHistory->namespace      =   'nexopos.read.expenses-history';
        $readHistory->description    =   __( 'Allow to the expense history.' );
        $readHistory->save();

        $deleteHistory                 =   new Permission;
        $deleteHistory->name           =   __( 'Delete Expense History' );
        $deleteHistory->namespace      =   'nexopos.delete.expenses-history';
        $deleteHistory->description    =   __( 'Allow to delete an expense history.' );
        $deleteHistory->save();

        Role::namespace( 'admin' )->addPermissions([
            $readHistory,
            $deleteHistory
        ]);

        Role::namespace( 'supervisor' )->addPermissions([
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
