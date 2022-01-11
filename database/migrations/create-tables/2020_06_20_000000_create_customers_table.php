<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::createIfMissing( 'nexopos_customers', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'surname' )->nullable();
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
                $table->string( 'gender' )->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'email' )->nullable();
                $table->string( 'pobox' )->nullable();
                $table->integer( 'group_id' );
                $table->datetime( 'birth_date' )->nullable();
                $table->float( 'purchases_amount', 18, 5 )->default(0);
                $table->float( 'owed_amount', 18, 5 )->default(0);
                $table->float( 'credit_limit_amount', 18, 5 )->default(0)->nullable();
                $table->float( 'account_amount', 18, 5 )->default(0);
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }

        if ( ! Schema::hasTable( 'nexopos_customers_addresses' ) ) {
            Schema::createIfMissing( 'nexopos_customers_addresses', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->string( 'type' ); // either "billing" | "shipping"
                $table->string( 'name' )->nullable();
                $table->string( 'email' )->nullable();
                $table->string( 'surname' )->nullable();
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
            });
        }

        if ( ! Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::createIfMissing( 'nexopos_customers_account_history', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->integer( 'order_id' )->nullable();
                $table->float( 'amount' )->default(0);
                $table->string( 'operation' ); // sub / add
                $table->integer( 'author' );
                $table->text( 'description' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_customers' );
        Schema::dropIfExists( 'nexopos_customers_addresses' );
        Schema::dropIfExists( 'nexopos_customers_account_history' );
    }
}

