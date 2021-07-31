<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashFlowHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'nexopos_cash_flow_history', function (Blueprint $table) {
            $table->id();
            $table->string( 'action' ); // income or expense
            $table->string( 'name' )->nullable();
            $table->float( 'value' )->default(0);
            $table->integer( 'reference' )->nullable();
            $table->text( 'description' )->nullable();
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
        Schema::dropIfExists( 'nexopos_cash_flow_history');
    }
}
