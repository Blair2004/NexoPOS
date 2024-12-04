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
        if ( ! Schema::hasTable( 'nexopos_orders' ) ) {
            Schema::createIfMissing( 'nexopos_orders', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->text( 'description' )->nullable();
                $table->string( 'code' );
                $table->string( 'title' )->nullable();
                $table->string( 'type' ); // delivery, in_store
                $table->string( 'payment_status' ); // paid, unpaid, partially_paid
                $table->string( 'process_status' )->default( 'pending' ); // complete, ongoing, pending
                $table->string( 'delivery_status' )->default( 'pending' ); // pending, shipped, delivered,
                $table->float( 'discount', 18, 5 )->default( 0 );
                $table->string( 'discount_type' )->nullable();
                $table->boolean( 'support_instalments' )->default( true ); // define whether an order should only be paid using instalments feature
                $table->float( 'discount_percentage', 18, 5 )->nullable();
                $table->float( 'shipping', 18, 5 )->default( 0 ); // could be set manually or computed based on shipping_rate and shipping_type
                $table->float( 'shipping_rate', 18, 5 )->default( 0 );
                $table->string( 'shipping_type' )->nullable(); // "flat" | "percentage" (based on the order total)
                $table->float( 'total_without_tax', 18, 5 )->default( 0 );
                $table->float( 'subtotal', 18, 5 )->default( 0 );
                $table->float( 'total_with_tax', 18, 5 )->default( 0 );
                $table->float( 'total_coupons', 18, 5 )->default( 0 );
                $table->float( 'total_cogs', 18, 5 )->default( 0 );
                $table->float( 'total', 18, 5 )->default( 0 );
                $table->float( 'tax_value', 18, 5 )->default( 0 );
                $table->float( 'products_tax_value' )->default( 0 );
                $table->float( 'total_tax_value' )->default( 0 );
                $table->integer( 'tax_group_id' )->nullable();
                $table->string( 'tax_type' )->nullable();
                $table->float( 'tendered', 18, 5 )->default( 0 );
                $table->float( 'change', 18, 5 )->default( 0 );
                $table->datetime( 'final_payment_date' )->nullable();
                $table->integer( 'total_instalments' )->default( 0 );
                $table->integer( 'customer_id' );
                $table->string( 'note' )->nullable();
                $table->string( 'note_visibility' )->nullable();
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->integer( 'register_id' )->nullable();
                $table->text( 'voidance_reason' )->nullable();
                $table->timestamps();
            } );
        }

        if ( ! Schema::hasTable( 'nexopos_orders_count' ) ) {
            Schema::createIfMissing( 'nexopos_orders_count', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'count' );
                $table->datetime( 'date' );
            } );
        }

        if ( ! Schema::hasTable( 'nexopos_orders_taxes' ) ) {
            Schema::createIfMissing( 'nexopos_orders_taxes', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'tax_id' )->nullable();
                $table->integer( 'order_id' )->nullable();
                $table->float( 'rate' );
                $table->string( 'tax_name' )->nullable();
                $table->float( 'tax_value' )->default( 0 );
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
        if ( Schema::hasTable( 'nexopos_orders' ) ) {
            Schema::dropIfExists( 'nexopos_orders' );
        }

        if ( Schema::hasTable( 'nexopos_orders_count' ) ) {
            Schema::dropIfExists( 'nexopos_orders_count' );
        }

        if ( Schema::hasTable( 'nexopos_orders_taxes' ) ) {
            Schema::dropIfExists( 'nexopos_orders_taxes' );
        }
    }
};
