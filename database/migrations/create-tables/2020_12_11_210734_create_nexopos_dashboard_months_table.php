<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;;

class CreateNexoposDashboardMonthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_dashboard_months', function (Blueprint $table) {
            $table->id();
            $table->float( 'month_taxes', 11, 5 )->default(0);
            $table->float( 'month_unpaid_orders', 11, 5 )->default(0);
            $table->float( 'month_unpaid_orders_count', 11, 5 )->default(0);
            $table->float( 'month_paid_orders', 11, 5 )->default(0);
            $table->float( 'month_paid_orders_count', 11, 5 )->default(0);
            $table->float( 'month_partially_paid_orders', 11, 5 )->default(0);
            $table->float( 'month_partially_paid_orders_count', 11, 5 )->default(0);
            $table->float( 'month_income', 11, 5 )->default(0);
            $table->float( 'month_discounts', 11, 5 )->default(0);
            $table->float( 'month_wasted_goods_count', 11, 5 )->default(0);
            $table->float( 'month_wasted_goods', 11, 5 )->default(0);
            $table->float( 'month_expenses', 11, 5 )->default(0);
            $table->float( 'total_wasted_goods', 11, 5 )->default(0);
            $table->float( 'total_unpaid_orders', 11, 5 )->default(0);
            $table->float( 'total_unpaid_orders_count', 11, 5 )->default(0);
            $table->float( 'total_paid_orders', 11, 5 )->default(0);
            $table->float( 'total_paid_orders_count', 11, 5 )->default(0);
            $table->float( 'total_partially_paid_orders', 11, 5 )->default(0);
            $table->float( 'total_partially_paid_orders_count', 11, 5 )->default(0);
            $table->float( 'total_income', 11, 5 )->default(0);
            $table->float( 'total_discounts', 11, 5 )->default(0);
            $table->float( 'total_taxes', 11, 5 )->default(0);
            $table->float( 'total_wasted_goods_count', 11, 5 )->default(0);
            $table->float( 'total_expenses', 11, 5 )->default(0);
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
        Schema::dropIfExists( 'nexopos_dashboard_months' );
    }
}
