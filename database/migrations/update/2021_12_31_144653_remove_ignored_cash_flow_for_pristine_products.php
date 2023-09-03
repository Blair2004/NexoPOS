<?php

use App\Models\TransactionHistory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $identifier = ns()->option->get( 'ns_stock_return_unspoiled_account' );

        TransactionHistory::where( 'expense_category_id', $identifier )->delete();

        ns()->option->delete( 'ns_stock_return_unspoiled_account' );
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
};
