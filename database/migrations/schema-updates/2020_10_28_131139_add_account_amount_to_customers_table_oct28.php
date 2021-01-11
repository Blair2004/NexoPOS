<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;

class AddAccountAmountToCustomersTableOct28 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_customers', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_customers', 'account_amount' ) ) {
                $table->float( 'account_amount' )->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::table('nexopos_customers', function (Blueprint $table) {
                if ( Schema::hasColumn( 'nexopos_customers', 'account_amount' ) ) {
                    $table->dropColumn( 'account_amount' );
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::dropIfExists( 'nexopos_customers_account_history' );
        }
    }
}
