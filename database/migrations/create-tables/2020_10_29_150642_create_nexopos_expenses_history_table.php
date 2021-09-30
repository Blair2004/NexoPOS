<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use App\Models\CashFlow;

;

class CreateNexoposExpensesHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_cash_flow', function (Blueprint $table) {
            $table->id();
            $table->integer( 'expense_id' )->nullable();
            $table->string( 'operation' ); // credit or debit
            $table->integer( 'expense_category_id' )->nullable();
            $table->integer( 'procurement_id' )->nullable(); // when the procurement is deleted the expense history will be deleted automatically as well.
            $table->integer( 'order_refund_id' )->nullable(); // to link an expense to an order refund.
            $table->integer( 'order_id' )->nullable(); // to link an expense to an order refund.
            $table->integer( 'register_history_id' )->nullable(); // if an expenses has been created from a register transaction
            $table->integer( 'customer_account_history_id' )->nullable(); // if a customer credit is generated
            $table->string( 'name' );
            $table->string( 'status' )->default( CashFlow::STATUS_ACTIVE );
            $table->float( 'value', 18, 5 )->default(0);
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
        Schema::dropIfExists( 'nexopos_cash_flow' );
    }
}
