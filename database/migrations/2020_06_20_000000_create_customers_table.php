<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
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
            Schema::create( 'nexopos_customers', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'surname' )->nullable();
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
                $table->string( 'gender' )->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'email' )->unique()->nullable();
                $table->string( 'pobox' )->nullable();
                $table->integer( 'group_id' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }

        if ( ! Schema::hasTable( 'nexopos_customers_addresses' ) ) {
            Schema::create( 'nexopos_customers_addresses', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->string( 'type' ); // either "billing" | "shipping"
                $table->string( 'name' )->nullable();
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
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::drop( 'nexopos_customers' );
        }
        if ( Schema::hasTable( 'nexopos_customers_addresses' ) ) {
            Schema::drop( 'nexopos_customers_addresses' );
        }
    }
}

