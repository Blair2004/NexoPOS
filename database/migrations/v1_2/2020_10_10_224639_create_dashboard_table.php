<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDashboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_dashboard_days' ) ) {
            Schema::create( 'nexopos_dashboard_days' , function (Blueprint $table) {
                $table->id();
    
                $table->float( 'total_unpaid_orders' )->default(0);
                $table->float( 'day_unpaid_orders' )->default(0);
    
                $table->float( 'total_unpaid_orders_count' )->default(0);
                $table->float( 'day_unpaid_orders_count' )->default(0);
    
                $table->float( 'total_paid_orders' )->default(0);
                $table->float( 'day_paid_orders' )->default(0);
    
                $table->float( 'total_paid_orders_count' )->default(0);
                $table->float( 'day_paid_orders_count' )->default(0);
    
                $table->float( 'total_partially_paid_orders' )->default(0);
                $table->float( 'day_partially_paid_orders' )->default(0);
    
                $table->float( 'total_partially_paid_orders_count' )->default(0);
                $table->float( 'day_partially_paid_orders_count' )->default(0);
    
                $table->float( 'total_income' )->default(0);
                $table->float( 'day_income' )->default(0);
    
                $table->float( 'total_discounts' )->default(0);
                $table->float( 'day_discounts' )->default(0);
    
                $table->float( 'total_wasted_goods_count' )->default(0);
                $table->float( 'day_wasted_goods_count' )->default(0);
    
                $table->float( 'total_wasted_goods' )->default(0);
                $table->float( 'day_wasted_goods' )->default(0);
    
                $table->float( 'total_expenses' )->default(0);
                $table->float( 'day_expenses' )->default(0);
    
                $table->integer( 'day_of_year' )->default(0);
    
                $table->datetime( 'range_starts' );
                $table->datetime( 'range_ends' );
            });
        }

        if ( ! Schema::hasTable( 'nexopos_dashboard_weeks' ) ) {
            Schema::create( 'nexopos_dashboard_weeks', function( Blueprint $table ) {
                $table->id();
    
                $table->float( 'total_gross_income' )->default(0);
                $table->float( 'total_taxes' )->default(0);
                $table->float( 'total_expenses' )->default(0);
                $table->float( 'total_net_income' )->default(0);
                $table->integer( 'week_number' )->default(0);
    
                $table->datetime( 'range_starts' );
                $table->datetime( 'range_ends' );
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_dashboard_days' );
        Schema::dropIfExists( 'nexopos_dashboard_week' );
    }
}
