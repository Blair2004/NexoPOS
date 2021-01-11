<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddBalanceToNeoxposRegisters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_registers', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_registers', 'balance' ) ) {
                $table->float( 'balance' )->default(0);
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
        Schema::table('nexopos_registers', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_registers', 'balance' ) ) {
                $table->dropColumn( 'balance' );
            }
        });
    }
}
