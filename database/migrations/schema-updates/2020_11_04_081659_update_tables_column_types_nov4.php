<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateTablesColumnTypesNov4 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_orders_products', [
                    'quantity',
                    'discount_percentage',
                    'discount',
                    'gross_price',
                    'unit_price',
                    'tax_value',
                    'net_price',
                    'total_gross_price',
                    'total_price',
                    'total_net_price',
                    'unit_id',
                    'unit_quantity_id',
                ]) ) {
                    $table->float( 'quantity', 11, 5 )->change();
                    $table->float( 'discount_percentage', 11, 5 )->change();
                    $table->float( 'discount', 11, 5 )->change();
                    $table->float( 'gross_price', 11, 5 )->change();
                    $table->float( 'unit_price', 11, 5 )->change();
                    $table->float( 'tax_value', 11, 5 )->change();
                    $table->float( 'net_price', 11, 5 )->change();
                    $table->float( 'total_gross_price', 11, 5 )->change();
                    $table->float( 'total_price', 11, 5 )->change();
                    $table->float( 'total_net_price', 11, 5 )->change();
                    $table->float( 'unit_id', 11, 5 )->change();
                    $table->float( 'unit_quantity_id', 11, 5 )->change();
                }
            });
        }
        
        if ( Schema::hasTable( 'nexopos_orders' ) ) {
            Schema::table( 'nexopos_orders', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_orders', [
                    'discount',
                    'discount_percentage',
                    'shipping',
                    'shipping_rate',
                    'net_total',
                    'total',
                    'tendered',
                    'change',
                    'tax_value',
                    'subtotal',
                ]) ) {
                    $table->float( 'discount', 11, 5 )->change();
                    $table->float( 'discount_percentage', 11, 5 )->change();
                    $table->float( 'shipping', 11, 5 )->change();
                    $table->float( 'shipping_rate', 11, 5 )->change();
                    $table->float( 'net_total', 11, 5 )->change();
                    $table->float( 'total', 11, 5 )->change();
                    $table->float( 'tendered', 11, 5 )->change();
                    $table->float( 'change', 11, 5 )->change();
                    $table->float( 'tax_value', 11, 5 )->change();
                    $table->float( 'subtotal', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_coupons' ) ) {
            Schema::table( 'nexopos_orders_coupons', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_orders_coupons', [
                    'value',
                ]) ) {
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_taxes' ) ) {
            Schema::table( 'nexopos_orders_taxes', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_orders_taxes', [
                    'tax_value',
                ]) ) {
                    $table->float( 'tax_value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_payments' ) ) {
            Schema::table( 'nexopos_orders_payments', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_orders_payments', [
                    'value',
                ]) ) {
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_expenses_history' ) ) {
            Schema::table( 'nexopos_expenses_history', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_expenses_history', [
                    'value',
                ]) ) {
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::table( 'nexopos_expenses', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_expenses', [
                    'value',
                ]) ) {
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_dashboard_weeks' ) ) {
            Schema::table( 'nexopos_dashboard_weeks', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_dashboard_weeks', [
                    'total_gross_income',
                    'total_taxes',
                    'total_expenses',
                    'total_net_income',
                ]) ) {
                    $table->float( 'total_gross_income', 11, 5 )->change();
                    $table->float( 'total_taxes', 11, 5 )->change();
                    $table->float( 'total_expenses', 11, 5 )->change();
                    $table->float( 'total_net_income', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_customers_coupons' ) ) {
            Schema::table( 'nexopos_customers_coupons', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_customers_coupons', [
                    'discount_value',
                    'minimum_cart_value',
                    'maximum_cart_value',
                    'limit_usage',
                ]) ) {
                    $table->float( 'discount_value', 11, 5 )->default(0)->change();
                    $table->float( 'minimum_cart_value', 11, 5 )->default(0)->change();
                    $table->float( 'maximum_cart_value', 11, 5 )->default(0)->change();
                    $table->float( 'limit_usage', 11, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::table( 'nexopos_customers_account_history', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_customers_account_history', [
                    'amount',
                ]) ) {
                    $table->float( 'amount', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            Schema::table( 'nexopos_customers', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_customers', [
                    'purchases_amount',
                    'owed_amount',
                    'account_amount',
                ]) ) {
                    $table->float( 'purchases_amount', 11, 5 )->change();
                    $table->float( 'owed_amount', 11, 5 )->change();
                    $table->float( 'account_amount', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_dashboard_days' ) ) {
            Schema::table( 'nexopos_dashboard_days', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_dashboard_days', [
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
                    'total_wasted_goods_count',
                    'day_wasted_goods_count',
                    'total_wasted_goods',
                    'day_wasted_goods',
                    'total_expenses',
                    'day_expenses',
                    'day_taxes',
                    'total_taxes',
                ]) ) {
                    $table->float( 'total_unpaid_orders', 11, 5 )->change();
                    $table->float( 'day_unpaid_orders', 11, 5 )->change();
                    $table->float( 'total_unpaid_orders_count', 11, 5 )->change();
                    $table->float( 'day_unpaid_orders_count', 11, 5 )->change();
                    $table->float( 'total_paid_orders', 11, 5 )->change();
                    $table->float( 'day_paid_orders', 11, 5 )->change();
                    $table->float( 'total_paid_orders_count', 11, 5 )->change();
                    $table->float( 'day_paid_orders_count', 11, 5 )->change();
                    $table->float( 'total_partially_paid_orders', 11, 5 )->change();
                    $table->float( 'day_partially_paid_orders', 11, 5 )->change();
                    $table->float( 'total_partially_paid_orders_count', 11, 5 )->change();
                    $table->float( 'day_partially_paid_orders_count', 11, 5 )->change();
                    $table->float( 'total_income', 11, 5 )->change();
                    $table->float( 'day_income', 11, 5 )->change();
                    $table->float( 'total_discounts', 11, 5 )->change();
                    $table->float( 'day_discounts', 11, 5 )->change();
                    $table->float( 'total_wasted_goods_count', 11, 5 )->change();
                    $table->float( 'day_wasted_goods_count', 11, 5 )->change();
                    $table->float( 'total_wasted_goods', 11, 5 )->change();
                    $table->float( 'day_wasted_goods', 11, 5 )->change();
                    $table->float( 'total_expenses', 11, 5 )->change();
                    $table->float( 'day_expenses', 11, 5 )->change();
                    $table->float( 'day_taxes', 11, 5 )->change();
                    $table->float( 'total_taxes', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_rewards_system' ) ) {
            Schema::table( 'nexopos_rewards_system', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_rewards_system', [
                    'target',
                ]) ) {
                    $table->float( 'target', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::table( 'nexopos_rewards_system_rules', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_rewards_system_rules', [
                    'from',
                    'to',
                    'reward',
                ]) ) {
                    $table->float( 'from', 11, 5 )->change();
                    $table->float( 'to', 11, 5 )->change();
                    $table->float( 'reward', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_providers' ) ) {
            Schema::table( 'nexopos_providers', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_providers', [
                    'amount_due',
                    'amount_paid',
                ]) ) {
                    $table->float( 'amount_due', 11, 5 )->change();
                    $table->float( 'amount_paid', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_taxes' ) ) {
            Schema::table( 'nexopos_products_taxes', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_products_taxes', [
                    'rate',
                    'value',
                ]) ) {
                    $table->float( 'rate', 11, 5 )->change();
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_histories' ) ) {
            Schema::table( 'nexopos_products_histories', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_products_histories', [
                    'before_quantity',
                    'quantity',
                    'after_quantity',
                    'unit_price',
                    'total_price',
                ]) ) {
                    $table->float( 'before_quantity', 11, 5 )->change();
                    $table->float( 'quantity', 11, 5 )->change();
                    $table->float( 'after_quantity', 11, 5 )->change();
                    $table->float( 'unit_price', 11, 5 )->change();
                    $table->float( 'total_price', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_registers_history', [
                    'value',
                ]) ) {
                    $table->float( 'value', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_transfers_products' ) ) {
            Schema::table( 'nexopos_transfers_products', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_transfers_products', [
                    'quantity',
                ]) ) {
                    $table->float( 'quantity', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_procurements_products', [
                    'gross_purchase_price',
                    'net_purchase_price',
                    'purchase_price',
                    'quantity',
                    'tax_value',
                    'total_purchase_price',
                ]) ) {
                    $table->float( 'gross_purchase_price', 11, 5 )->change();
                    $table->float( 'net_purchase_price', 11, 5 )->change();
                    $table->float( 'purchase_price', 11, 5 )->change();
                    $table->float( 'quantity', 11, 5 )->change();
                    $table->float( 'tax_value', 11, 5 )->change();
                    $table->float( 'total_purchase_price', 11, 5 )->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
                if ( Schema::hasColumns( 'nexopos_products_unit_quantities', [
                    'quantity',
                    'sale_price_tax',
                    'sale_price',
                    'sale_price_edit',
                    'excl_tax_sale_price',
                    'incl_tax_sale_price',
                    'wholesale_price_tax',
                    'wholesale_price',
                    'wholesale_price_edit',
                    'incl_tax_wholesale_price',
                    'excl_tax_wholesale_price',
                ]) ) {
                    $table->float( 'quantity', 11, 5 )->change();
                    $table->float( 'sale_price_tax', 11, 5 )->change();
                    $table->float( 'sale_price', 11, 5 )->change();
                    $table->float( 'sale_price_edit', 11, 5 )->change();
                    $table->float( 'excl_tax_sale_price', 11, 5 )->change();
                    $table->float( 'incl_tax_sale_price', 11, 5 )->change();
                    $table->float( 'wholesale_price_tax', 11, 5 )->change();
                    $table->float( 'wholesale_price', 11, 5 )->change();
                    $table->float( 'wholesale_price_edit', 11, 5 )->change();
                    $table->float( 'incl_tax_wholesale_price', 11, 5 )->change();
                    $table->float( 'excl_tax_wholesale_price', 11, 5 )->change();
                }
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
        //
    }
}
