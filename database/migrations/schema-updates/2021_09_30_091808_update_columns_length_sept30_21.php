<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsLengthSept3021 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements', 'value' ) ) {
                $table->float( 'value', 18, 5 )->change();
            }
        });

        Schema::table( 'nexopos_customers', function( Blueprint $table ) {
            $table->float( 'purchases_amount', 18, 5 )->change();
            $table->float( 'owed_amount', 18, 5 )->change();
            $table->float( 'account_amount', 18, 5 )->change();
        });

        Schema::table( 'nexopos_expenses', function( Blueprint $table ) {
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders_coupons', function( Blueprint $table ) {
            $table->float( 'discount_value', 18, 5 )->change();
            $table->float( 'minimum_cart_value', 18, 5 )->change();
            $table->float( 'maximum_cart_value', 18, 5 )->change();
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders_payments', function( Blueprint $table ) {
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_products_histories', function( Blueprint $table ) {
            $table->float( 'before_quantity', 18, 5 )->change();
            $table->float( 'quantity', 18, 5 )->change();
            $table->float( 'after_quantity', 18, 5 )->change();
            $table->float( 'unit_price', 18, 5 )->change();
            $table->float( 'total_price', 18, 5 )->change();
        });

        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            $table->float( 'tax_value', 18, 5 )->change(); // computed automatically
        });

        Schema::table( 'nexopos_products_taxes', function( Blueprint $table ) {
            $table->float( 'rate', 18, 5 )->change();
            $table->float( 'value', 18, 5 )->change(); // actual computed tax value
        });

        Schema::table( 'nexopos_providers', function( Blueprint $table ) {
            $table->float( 'amount_due', 18, 5 )->change();
            $table->float( 'amount_paid', 18, 5 )->change();
        });

        Schema::table( 'nexopos_units', function( Blueprint $table ) {
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_registers_history', function( Blueprint $table ) {
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_taxes', function( Blueprint $table ) {
            $table->float( 'rate', 18, 5 )->change();
        });

        Schema::table( 'nexopos_rewards_system', function( Blueprint $table ) {
            $table->float( 'target', 18, 5 )->change();
        });

        Schema::table( 'nexopos_rewards_system_rules', function( Blueprint $table ) {
            $table->float( 'from', 18, 5 )->change();
            $table->float( 'to', 18, 5 )->change();
            $table->float( 'reward', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders_instalments', function( Blueprint $table ) {
            $table->float( 'amount', 18, 5 )->change();
        });

        Schema::table( 'nexopos_registers', function( Blueprint $table ) {
            $table->float( 'balance', 18, 5 )->change();
        });

        Schema::table( 'nexopos_customers_rewards', function( Blueprint $table ) {
            $table->float( 'points', 18, 5 )->change();
            $table->float( 'target', 18, 5 )->change();
        });

        Schema::table( 'nexopos_coupons', function( Blueprint $table ) {
            $table->float( 'discount_value', 18, 5 )->change();
            $table->float( 'minimum_cart_value', 18, 5 )->change();
            $table->float( 'maximum_cart_value', 18, 5 )->change();
            $table->float( 'limit_usage', 18, 5 )->change();
        });

        Schema::table( 'nexopos_dashboard_months', function( Blueprint $table ) {
            $table->float( 'month_taxes', 18, 5 )->change();
            $table->float( 'month_unpaid_orders', 18, 5 )->change();
            $table->float( 'month_unpaid_orders_count', 18, 5 )->change();
            $table->float( 'month_paid_orders', 18, 5 )->change();
            $table->float( 'month_paid_orders_count', 18, 5 )->change();
            $table->float( 'month_partially_paid_orders', 18, 5 )->change();
            $table->float( 'month_partially_paid_orders_count', 18, 5 )->change();
            $table->float( 'month_income', 18, 5 )->change();
            $table->float( 'month_discounts', 18, 5 )->change();
            $table->float( 'month_wasted_goods_count', 18, 5 )->change();
            $table->float( 'month_wasted_goods', 18, 5 )->change();
            $table->float( 'month_expenses', 18, 5 )->change();
            $table->float( 'total_wasted_goods', 18, 5 )->change();
            $table->float( 'total_unpaid_orders', 18, 5 )->change();
            $table->float( 'total_unpaid_orders_count', 18, 5 )->change();
            $table->float( 'total_paid_orders', 18, 5 )->change();
            $table->float( 'total_paid_orders_count', 18, 5 )->change();
            $table->float( 'total_partially_paid_orders', 18, 5 )->change();
            $table->float( 'total_partially_paid_orders_count', 18, 5 )->change();
            $table->float( 'total_income', 18, 5 )->change();
            $table->float( 'total_discounts', 18, 5 )->change();
            $table->float( 'total_taxes', 18, 5 )->change();
            $table->float( 'total_wasted_goods_count', 18, 5 )->change();
            $table->float( 'total_expenses', 18, 5 )->change();
        });

        Schema::table( 'nexopos_cash_flow', function( Blueprint $table ) {
            $table->float( 'value', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders_refunds', function( Blueprint $table ) {
            $table->float( 'total', 18, 5 )->change();
            $table->float( 'tax_value', 18, 5 )->change();
            $table->float( 'shipping', 18, 5 )->change();
        });
        
        Schema::table( 'nexopos_orders_products_refunds', function( Blueprint $table ) {
            $table->float( 'unit_price', 18, 5 )->change();
            $table->float( 'tax_value', 18, 5 )->change();
            $table->float( 'quantity', 18, 5 )->change();
            $table->float( 'total_price', 18, 5 )->change();
        });

        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            $table->float( 'quantity', 18, 5 )->change();
            $table->float( 'sale_price', 18, 5 )->change();
            $table->float( 'sale_price_edit', 18, 5 )->change();
            $table->float( 'excl_tax_sale_price', 18, 5 )->change();
            $table->float( 'incl_tax_sale_price', 18, 5 )->change();
            $table->float( 'sale_price_tax', 18, 5 )->change();
            $table->float( 'wholesale_price', 18, 5 )->change();
            $table->float( 'wholesale_price_edit', 18, 5 )->change();
            $table->float( 'incl_tax_wholesale_price', 18, 5 )->change();
            $table->float( 'excl_tax_wholesale_price', 18, 5 )->change();
            $table->float( 'wholesale_price_tax', 18, 5 )->change();
            $table->float( 'custom_price', 18, 5 )->change();
            $table->float( 'custom_price_edit', 18, 5 )->change();
            $table->float( 'incl_tax_custom_price', 18, 5 )->change();
            $table->float( 'excl_tax_custom_price', 18, 5 )->change();
        });

        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            $table->float( 'gross_purchase_price', 18, 5 )->change();
            $table->float( 'net_purchase_price', 18, 5 )->change();
            $table->float( 'purchase_price', 18, 5 )->change();
            $table->float( 'quantity', 18, 5 )->change();
            $table->float( 'available_quantity', 18, 5 )->change();
            $table->float( 'tax_value', 18, 5 )->change();
            $table->float( 'total_purchase_price', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            $table->float( 'discount', 18, 5 )->change();
            $table->float( 'discount_percentage', 18, 5 )->change();
            $table->float( 'shipping', 18, 5 )->change(); // could be set manually or computed based on shipping_rate and shipping_type
            $table->float( 'shipping_rate', 18, 5 )->change();
            $table->float( 'gross_total', 18, 5 )->change();
            $table->float( 'subtotal', 18, 5 )->change();
            $table->float( 'net_total', 18, 5 )->change();
            $table->float( 'total_coupons', 18, 5 )->change();
            $table->float( 'total', 18, 5 )->change();
            $table->float( 'tax_value', 18, 5 )->change();
            $table->float( 'tendered', 18, 5 )->change();
            $table->float( 'change', 18, 5 )->change();
        });

        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            $table->float( 'quantity', 18, 5 )->change(); // could be the base unit
            $table->float( 'discount', 18, 5 )->change();
            $table->float( 'discount_percentage', 18, 5 )->change();
            $table->float( 'gross_price', 18, 5 )->change();
            $table->float( 'unit_price', 18, 5 )->change();
            $table->float( 'tax_value', 18, 5 )->change();
            $table->float( 'net_price', 18, 5 )->change();
            $table->float( 'total_gross_price', 18, 5 )->change();
            $table->float( 'total_price', 18, 5 )->change();
            $table->float( 'total_purchase_price', 18, 5 )->change();
            $table->float( 'total_net_price', 18, 5 )->change();
        });

        Schema::table( 'nexopos_dashboard_days', function (Blueprint $table) {
            foreach([
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
                if ( Schema::hasColumn( 'nexopos_dashboard_days', $column ) ) {
                    $table->float( $column, 18, 5 )->change();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
