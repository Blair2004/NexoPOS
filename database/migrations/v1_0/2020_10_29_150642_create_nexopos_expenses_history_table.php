<?php

use App\Classes\Hook;
use App\Models\ExpenseHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNexoposExpensesHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_expenses_history' ), function (Blueprint $table) {
            $table->id();
            $table->integer( 'expense_id' );
            $table->string( 'expense_name' );
            $table->string( 'status' )->default( ExpenseHistory::STATUS_ACTIVE );
            $table->string( 'expense_category_name' )->nullable();
            $table->float( 'value' )->default(0);
            $table->integer( 'author' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_expenses_history') );
    }
}
