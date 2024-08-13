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
        if ( ! Schema::hasTable( 'nexopos_transactions_accounts' ) ) {
            Schema::createIfMissing( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'operation' )->default( 'debit' ); // "credit" or "debit".
                $table->string( 'account' )->default( 0 );
                $table->integer( 'counter_account_id' )->default( 0 )->nullable();
                $table->string( 'category_identifier' )->nullable();
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
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
        Schema::dropIfExists( 'nexopos_transactions_accounts' );
    }
};
