<?php

use App\Models\ExpenseHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Dec8AddColumnsToExpensesHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_expenses_history', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'status' ) ) {
                $table->string( 'status' )->default( ExpenseHistory::STATUS_ACTIVE );
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
        //
    }
}
