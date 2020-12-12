<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNexoposDashboardMonthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nexopos_dashboard_months', function (Blueprint $table) {
            $table->id();
            $table->float( 'month_taxes' )->default(0);
            $table->float( 'month_unpaid_orders' )->default(0);
            $table->float( 'month_unpaid_orders_count' )->default(0);
            $table->float( 'month_paid_orders' )->default(0);
            $table->float( 'month_paid_orders_count' )->default(0);
            $table->float( 'month_partially_paid_orders' )->default(0);
            $table->float( 'month_partially_paid_orders_count' )->default(0);
            $table->float( 'month_income' )->default(0);
            $table->float( 'month_discounts' )->default(0);
            $table->float( 'month_wasted_goods_count' )->default(0);
            $table->float( 'month_wasted_goods' )->default(0);
            $table->float( 'month_expenses' )->default(0);
            $table->float( 'total_wasted_goods' )->default(0);
            $table->float( 'total_unpaid_orders' )->default(0);
            $table->float( 'total_unpaid_orders_count' )->default(0);
            $table->float( 'total_paid_orders' )->default(0);
            $table->float( 'total_paid_orders_count' )->default(0);
            $table->float( 'total_partially_paid_orders' )->default(0);
            $table->float( 'total_partially_paid_orders_count' )->default(0);
            $table->float( 'total_income' )->default(0);
            $table->float( 'total_discounts' )->default(0);
            $table->float( 'total_taxes' )->default(0);
            $table->float( 'total_wasted_goods_count' )->default(0);
            $table->float( 'total_expenses' )->default(0);
            $table->integer( 'month_of_year' )->default(0);
            $table->datetime( 'range_starts' );
            $table->datetime( 'range_ends' );
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
        Schema::dropIfExists('nexopos_dashboard_months');
    }
}
