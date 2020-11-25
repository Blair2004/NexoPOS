<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->text( 'description' )->nullable();
                $table->string( 'code' );
                $table->string( 'title' )->nullable();
                $table->string( 'type' ); // delivery, in_store
                $table->string( 'payment_status' ); // paid, unpaid, partially_paid
                $table->string( 'process_status' )->default( 'pending' ); // complete, ongoing, pending
                $table->string( 'delivery_status' )->default( 'pending' ); // pending, shipped, delivered, 
                $table->float( 'discount' )->default(0);
                $table->string( 'discount_type' )->nullable();
                $table->float( 'discount_percentage' )->nullable();
                $table->float( 'shipping' )->default(0); // could be set manually or computed based on shipping_rate and shipping_type
                $table->float( 'shipping_rate' )->default(0);
                $table->string( 'shipping_type' )->nullable(); // "flat" | "percentage" (based on the order total)
                $table->float( 'gross_total' )->default(0);
                $table->float( 'subtotal' )->default(0);
                $table->float( 'net_total' )->default(0);
                $table->float( 'total' )->default(0);
                $table->float( 'tax_value' )->default(0);
                $table->float( 'tendered' )->default(0);
                $table->float( 'change' )->default(0);
                $table->datetime( 'expected_payment_date' )->nullable();
                $table->integer( 'total_installments' )->default(0);
                $table->integer( 'customer_id' );
                // $table->string( 'payment' );
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->text( 'voidance_reason' )->nullable();
                $table->timestamps();
            });
        }

        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_count' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_count' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'count' );
                $table->datetime( 'date' );
            });
        }

        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_taxes' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_taxes' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'tax_id' )->nullable();
                $table->integer( 'order_id' )->nullable();
                $table->string( 'tax_name' )->nullable();
                $table->float( 'tax_value' )->default(0);
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
        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders' ) );
        }

        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_count' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_count' ) );
        }

        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_taxes' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_taxes' ) );
        }
    }
}

