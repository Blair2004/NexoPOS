<?php

use App\Classes\Schema;
use App\Models\TransactionHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_transactions_histories', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'transaction_id' )->nullable();
            $table->string( 'operation' ); // credit or debit
            $table->boolean( 'is_reflection' )->default( false );
            $table->integer( 'reflection_source_id' )->nullable();
            $table->integer( 'transaction_account_id' )->nullable();
            $table->integer( 'procurement_id' )->nullable(); // when the procurement is deleted the transaction history will be deleted automatically as well.
            $table->integer( 'order_refund_id' )->nullable(); // to link an transaction to an order refund.
            $table->integer( 'order_payment_id' )->nullable(); // to link to a payment id.
            $table->integer( 'order_refund_product_id' )->nullable(); // link the refund to an order refund product
            $table->integer( 'order_id' )->nullable(); // to link an transaction to an order.
            $table->integer( 'order_product_id' )->nullable(); // link the refund to an order product
            $table->integer( 'register_history_id' )->nullable(); // if an transactions has been created from a register transaction
            $table->integer( 'customer_account_history_id' )->nullable(); // if a customer credit is generated
            $table->string( 'name' );
            $table->string( 'type' )->nullable();
            $table->string( 'status' )->default( TransactionHistory::STATUS_PENDING ); // active, pending, deleting
            $table->float( 'value', 18, 5 )->default( 0 );
            $table->datetime( 'trigger_date' )->nullable();
            $table->integer( 'rule_id' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_transactions_histories' );
    }
};
