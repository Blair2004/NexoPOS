<?php

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
        Schema::createIfMissing( 'nexopos_dashboard_months', function ( Blueprint $table ) {
            $table->id();
            $table->float( 'month_taxes', 18, 5 )->default( 0 );
            $table->float( 'month_unpaid_orders', 18, 5 )->default( 0 );
            $table->float( 'month_unpaid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'month_paid_orders', 18, 5 )->default( 0 );
            $table->float( 'month_paid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'month_partially_paid_orders', 18, 5 )->default( 0 );
            $table->float( 'month_partially_paid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'month_income', 18, 5 )->default( 0 );
            $table->float( 'month_discounts', 18, 5 )->default( 0 );
            $table->float( 'month_wasted_goods_count', 18, 5 )->default( 0 );
            $table->float( 'month_wasted_goods', 18, 5 )->default( 0 );
            $table->float( 'month_expenses', 18, 5 )->default( 0 );
            $table->float( 'total_wasted_goods', 18, 5 )->default( 0 );
            $table->float( 'total_unpaid_orders', 18, 5 )->default( 0 );
            $table->float( 'total_unpaid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'total_paid_orders', 18, 5 )->default( 0 );
            $table->float( 'total_paid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'total_partially_paid_orders', 18, 5 )->default( 0 );
            $table->float( 'total_partially_paid_orders_count', 18, 5 )->default( 0 );
            $table->float( 'total_income', 18, 5 )->default( 0 );
            $table->float( 'total_discounts', 18, 5 )->default( 0 );
            $table->float( 'total_taxes', 18, 5 )->default( 0 );
            $table->float( 'total_wasted_goods_count', 18, 5 )->default( 0 );
            $table->float( 'total_expenses', 18, 5 )->default( 0 );
            $table->integer( 'month_of_year' )->default( 0 );
            $table->datetime( 'range_starts' );
            $table->datetime( 'range_ends' );
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
        Schema::dropIfExists( 'nexopos_dashboard_months' );
    }
};
