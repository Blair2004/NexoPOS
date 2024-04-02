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
        if ( ! Schema::hasTable( 'nexopos_dashboard_days' ) ) {
            Schema::createIfMissing( 'nexopos_dashboard_days', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'id' ) ) {
                    $table->bigIncrements( 'id' );
                }
            } );
        }

        if ( Schema::hasTable( 'nexopos_dashboard_days' ) ) {
            Schema::table( 'nexopos_dashboard_days', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'id' ) ) {
                    $table->bigIncrements( 'id' );
                }

                foreach ( [
                    'total_unpaid_orders',
                    'day_unpaid_orders',

                    'total_unpaid_orders_count',
                    'day_unpaid_orders_count',

                    'total_paid_orders',
                    'day_paid_orders',

                    'total_paid_orders_count',
                    'day_paid_orders_count',

                    'total_partially_paid_orders',
                    'day_partially_paid_orders',

                    'total_partially_paid_orders_count',
                    'day_partially_paid_orders_count',

                    'total_income',
                    'day_income',

                    'total_discounts',
                    'day_discounts',

                    'day_taxes',
                    'total_taxes',

                    'total_wasted_goods_count',
                    'day_wasted_goods_count',

                    'total_wasted_goods',
                    'day_wasted_goods',

                    'total_expenses',
                    'day_expenses',
                ] as $column ) {
                    if ( ! Schema::hasColumn( 'nexopos_dashboard_days', $column ) ) {
                        $table->float( $column, 18, 5 )->default( 0 );
                    }
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'day_of_year' ) ) {
                    $table->integer( 'day_of_year' )->default( 0 );
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'range_starts' ) ) {
                    $table->datetime( 'range_starts' )->nullable();
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'range_ends' ) ) {
                    $table->datetime( 'range_ends' )->nullable();
                }
            } );
        }

        if ( ! Schema::hasTable( 'nexopos_dashboard_weeks' ) ) {
            Schema::createIfMissing( 'nexopos_dashboard_weeks', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_dashboard_weeks', 'id' ) ) {
                    $table->bigIncrements( 'id' );
                }
            } );
        }

        if ( Schema::hasTable( 'nexopos_dashboard_weeks' ) ) {
            Schema::table( 'nexopos_dashboard_weeks', function ( Blueprint $table ) {
                foreach ( [
                    'total_gross_income',
                    'total_taxes',
                    'total_expenses',
                    'total_net_income',
                ] as $column ) {
                    if ( ! Schema::hasColumn( 'nexopos_dashboard_weeks', $column ) ) {
                        $table->float( $column, 18, 5 )->default( 0 );
                    }
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_weeks', 'week_number' ) ) {
                    $table->integer( 'week_number' )->default( 0 );
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_weeks', 'range_starts' ) ) {
                    $table->datetime( 'range_starts' )->nullable();
                }

                if ( ! Schema::hasColumn( 'nexopos_dashboard_weeks', 'range_ends' ) ) {
                    $table->datetime( 'range_ends' )->nullable();
                }
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
        Schema::dropIfExists( 'nexopos_dashboard_days' );
        Schema::dropIfExists( 'nexopos_dashboard_weeks' );
    }
};
