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
        if ( ! Schema::hasTable( 'nexopos_customers_addresses' ) ) {
            Schema::createIfMissing( 'nexopos_customers_addresses', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->string( 'type' ); // either "billing" | "shipping"
                $table->string( 'email' )->nullable();
                $table->string( 'first_name' )->nullable();
                $table->string( 'last_name' )->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'address_1' )->nullable();
                $table->string( 'address_2' )->nullable();
                $table->string( 'country' )->nullable();
                $table->string( 'city' )->nullable();
                $table->string( 'pobox' )->nullable();
                $table->string( 'company' )->nullable();
                $table->string( 'uuid' )->nullable();
                $table->integer( 'author' );
                $table->timestamps();
            } );
        }

        if ( ! Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::createIfMissing( 'nexopos_customers_account_history', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->integer( 'order_id' )->nullable();
                $table->float( 'previous_amount' )->default( 0 );
                $table->float( 'amount' )->default( 0 );
                $table->float( 'next_amount' )->default( 0 );
                $table->string( 'operation' ); // sub / add
                $table->integer( 'author' );
                $table->text( 'description' )->nullable();
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
        Schema::dropIfExists( 'nexopos_customers_addresses' );
        Schema::dropIfExists( 'nexopos_customers_account_history' );
    }
};
