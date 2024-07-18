<?php
/**
 * Table Migration
**/

use App\Classes\Schema;
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
        if ( ! Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::createIfMissing( 'nexopos_registers_history', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'register_id' );
                $table->integer( 'payment_id' )->nullable();
                $table->integer( 'transaction_account_id' )->nullable();
                $table->integer( 'payment_type_id' )->default( 0 );
                $table->integer( 'order_id' )->nullable();
                $table->string( 'action' );
                $table->integer( 'author' );
                $table->float( 'value', 18, 5 )->default( 0 );
                $table->text( 'description' )->nullable();
                $table->string( 'uuid' )->nullable();
                $table->float( 'balance_before', 18, 5 )->default( 0 );
                $table->string( 'transaction_type' )->nullable(); // can be "unchanged", "negative", "positive".
                $table->float( 'balance_after', 18, 5 )->default( 0 );
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::dropIfExists( 'nexopos_registers_history' );
        }
    }
};
