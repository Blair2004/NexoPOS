<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_orders_addresses' ) ) {
            Schema::createIfMissing( 'nexopos_orders_addresses', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'order_id' );
                $table->string( 'type' ); // either "billing" or "shipping"
                $table->string( 'name' )->nullable();
                $table->string( 'surname' )->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'address_1' )->nullable();
                $table->string ( 'email' )->nullable();
                $table->string( 'address_2' )->nullable();
                $table->string( 'country' )->nullable();
                $table->string( 'city' )->nullable();
                $table->string( 'pobox' )->nullable();
                $table->string( 'company' )->nullable();
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_orders_addresses' ) ) {
            Schema::drop( 'nexopos_orders_addresses' );
        }
    }
}

